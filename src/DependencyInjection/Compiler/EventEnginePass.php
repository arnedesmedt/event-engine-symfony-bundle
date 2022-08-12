<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection\Compiler;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Command\Command;
use ADS\Bundle\EventEngineBundle\Command\ControllerCommand;
use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Event\Listener;
use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Bundle\EventEngineBundle\Repository\Repository;
use ADS\Bundle\EventEngineBundle\Repository\StateRepository;
use ADS\Bundle\EventEngineBundle\Type\Type;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\Util\StringUtil;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\EventEngineDescription;
use EventEngine\Messaging\MessageProducer;
use Exception;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function array_merge;
use function array_reduce;
use function preg_match_all;
use function sprintf;
use function str_replace;
use function strtolower;
use function strval;
use function substr;

final class EventEnginePass implements CompilerPassInterface
{
    private const SERVICES_TO_MAKE_PUBLIC = [
        'event_engine.resolvers',
        'event_engine.listeners',
        'event_engine.pre_processors',
        'event_engine.controllers',
        'event_engine.projectors',
    ];

    public function process(ContainerBuilder $container): void
    {
        $this->addImmutablesAsParameters($container);
        $this->addEventQueue($container);

        $this->buildRepositories($container);
        $this->buildProjectors($container);
        $container->removeDefinition(Repository::class);
        $this->makeDependenciesPublic($container);
    }

    private function addImmutablesAsParameters(ContainerBuilder $container): void
    {
        $filterClosuresPerParameter = [
            'commands' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(Command::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'controllers' => static function (ReflectionClass $reflectionClass) use ($container) {
                if (
                    ! $reflectionClass->implementsInterface(ControllerCommand::class)
                    || $reflectionClass->isAbstract()
                ) {
                    return null;
                }

                /** @var class-string<ControllerCommand> $controllerCommandClass */
                $controllerCommandClass = $reflectionClass->getName();

                return $container->getReflectionClass($controllerCommandClass::__controller());
            },
            'queries' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(Query::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'resolvers' => static function (ReflectionClass $reflectionClass) use ($container) {
                if (! $reflectionClass->implementsInterface(Query::class) || $reflectionClass->isAbstract()) {
                    return null;
                }

                /** @var class-string<Query> $queryClass */
                $queryClass = $reflectionClass->getName();

                return $container->getReflectionClass($queryClass::__resolver());
            },
            'events' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(Event::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'aggregates' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(AggregateRoot::class)
                ? $reflectionClass
                : null,
            'pre_processors' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(PreProcessor::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'listeners' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(Listener::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'descriptions' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(EventEngineDescription::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'repositories' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(StateRepository::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'types' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(Type::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
            'projectors' => static fn (ReflectionClass $reflectionClass) => $reflectionClass
                ->implementsInterface(Projector::class) && ! $reflectionClass->isAbstract()
                ? $reflectionClass
                : null,
        ];

        foreach ($filterClosuresPerParameter as $parameter => $closure) {
            $parameterName = sprintf('event_engine.%s', $parameter);
            $container->setParameter($parameterName, []);
        }

        foreach ($container->getResources() as $resource) {
            $reflectionClass = $this->reflectionClassFromResource($container, $resource);

            if ($reflectionClass === null) {
                continue;
            }

            foreach ($filterClosuresPerParameter as $parameter => $closure) {
                /** @var ReflectionClass<object>|null $transformedReflectionClass */
                $transformedReflectionClass = $closure($reflectionClass);
                if (! $transformedReflectionClass) {
                    continue;
                }

                $parameterName = sprintf('event_engine.%s', $parameter);
                /** @var array<class-string> $containerParameter */
                $containerParameter = $container->getParameter($parameterName);
                $containerParameter[] = $transformedReflectionClass->getName();
                $container->setParameter($parameterName, $containerParameter);
            }
        }
    }

    private function addEventQueue(ContainerBuilder $container): void
    {
        foreach ($container->getResources() as $resource) {
            $reflectionClass = $this->reflectionClassFromResource($container, $resource);

            if ($reflectionClass === null || ! $reflectionClass->implementsInterface(MessageProducer::class)) {
                continue;
            }

            if ($container->hasDefinition('event_engine.event_queue')) {
                throw new Exception('You can only have 1 event queue.');
            }

            $eventQueueDefinition = $container->hasDefinition($reflectionClass->getName())
                ? $container->getDefinition($reflectionClass->getName())
                : new Definition($reflectionClass->getName());

            $container->setDefinition('event_engine.event_queue', $eventQueueDefinition);
        }
    }

    private function buildRepositories(ContainerBuilder $container): void
    {
        $repository = $container->getDefinition(Repository::class);
        /** @var array<class-string> $repositories */
        $repositories = $container->getParameter('event_engine.repositories');
        /** @var array<class-string> $aggregates */
        $aggregates = $container->getParameter('event_engine.aggregates');
        /** @var string $entityNamespace */
        $entityNamespace = $container->getParameter('event_engine.entity_namespace');

        $aggregateRepositoryDefinitions = array_reduce(
            $aggregates,
            static function (array $result, $aggregate) use ($entityNamespace, $repository) {
                $aggregateShortName = (new ReflectionClass($aggregate))->getShortName();
                $identifier = sprintf('event_engine.repository.%s', StringUtil::decamelize($aggregateShortName));

                $result[$identifier] = (new Definition(
                    $repository->getClass(),
                    [
                        new Reference(DocumentStore::class),
                        EventEngineUtil::fromAggregateNameToDocumentStoreName($aggregateShortName),
                        EventEngineUtil::fromAggregateNameToStateClass($aggregateShortName, $entityNamespace),
                        EventEngineUtil::fromAggregateNameToStatesClass($aggregateShortName, $entityNamespace),
                    ]
                ))
                    ->setPublic(true);

                return $result;
            },
            [],
        );

        $container->addDefinitions($aggregateRepositoryDefinitions);

        foreach ($repositories as $repository) {
            preg_match_all('/\\\([^\\\]+)Repository$/', $repository, $matches);

            $container->getDefinition($repository)
                ->setArguments(
                    $container
                        ->getDefinition(
                            sprintf(
                                'event_engine.repository.%s',
                                strtolower(StringUtil::decamelize($matches[1][0]))
                            )
                        )
                        ->getArguments()
                )
                ->setPublic(true);
        }
    }

    private function buildProjectors(ContainerBuilder $container): void
    {
        $repository = $container->getDefinition(Repository::class);
        /** @var array<class-string<Projector>> $projectors */
        $projectors = $container->getParameter('event_engine.projectors');

        $projectorRepositoryDefinitions = array_reduce(
            $projectors,
            static function (array $result, $projector) use ($repository): array {
                $projectorName = (new ReflectionClass($projector))->getShortName();

                $identifier = str_replace(
                    '_projector',
                    '',
                    sprintf('event_engine.repository.%s', StringUtil::decamelize($projectorName))
                );

                $result[$identifier] = (new Definition(
                    $repository->getClass(),
                    [
                        new Reference(DocumentStore::class),
                        $projector::generateOwnCollectionName(),
                        $projector::stateClassName(),
                        $projector::statesClassName(),
                    ]
                ))->setPublic(true);

                return $result;
            },
            []
        );

        $container->addDefinitions($projectorRepositoryDefinitions);
    }

    private function makeDependenciesPublic(ContainerBuilder $container): void
    {
        $services = [];

        foreach (self::SERVICES_TO_MAKE_PUBLIC as $serviceToMakePublic) {
            /** @var array<class-string> $extraServices */
            $extraServices = $container->getParameter($serviceToMakePublic);

            $services = array_merge(
                $services,
                $extraServices
            );
        }

        foreach ($services as $service) {
            if (! $container->hasDefinition($service)) {
                throw new RuntimeException(
                    sprintf(
                        'Service \'%s\' can\'t be made public because it\'s not found.',
                        $service
                    )
                );
            }

            $container->getDefinition($service)->setPublic(true);
        }
    }

    /**
     * @return ReflectionClass<object>|null
     */
    private function reflectionClassFromResource(
        ContainerBuilder $container,
        ResourceInterface $resource
    ): ?ReflectionClass {
        if (! $resource instanceof ReflectionClassResource) {
            return null;
        }

        $className = substr(strval($resource), 11);

        return $container->getReflectionClass($className);
    }
}
