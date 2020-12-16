<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection\Compiler;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Command\Command;
use ADS\Bundle\EventEngineBundle\Command\ControllerCommand;
use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Event\Listener;
use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Bundle\EventEngineBundle\Repository\Repository;
use ADS\Bundle\EventEngineBundle\Type\Type;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\Bundle\EventEngineBundle\Util\StringUtil;
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

use function array_filter;
use function array_map;
use function array_reduce;
use function array_unique;
use function preg_match_all;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;

final class EventEnginePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $domainNamespace = $container->getParameter('event_engine.domain_namespace');
        $filter = sprintf('reflection.%s', $domainNamespace);

        $resources = array_filter(
            $container->getResources(),
            static function (ResourceInterface $resource) use ($filter) {
                return $resource instanceof ReflectionClassResource
                    && strpos($resource . '', $filter) === 0;
            }
        );

        $resources = array_map(
            static function (ReflectionClassResource $resource) {
                /** @var class-string $class */
                $class = substr($resource . '', 11);

                return new ReflectionClass($class);
            },
            $resources
        );

        $mappers = [
            'commands' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(Command::class)
                    ? $reflectionClass->name
                    : null;
            },
            'queries' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(Query::class)
                    ? $reflectionClass->name
                    : null;
            },
            'resolvers' => static function (ReflectionClass $reflectionClass) {
                /** @var class-string $className */
                $className = $reflectionClass->name;

                return $reflectionClass->implementsInterface(Query::class)
                    ? $className::__resolver()
                    : null;
            },
            'events' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(Event::class)
                    ? $reflectionClass->name
                    : null;
            },
            'aggregates' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(AggregateRoot::class)
                    ? $reflectionClass->name
                    : null;
            },
            'pre_processors' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(PreProcessor::class)
                    ? $reflectionClass->name
                    : null;
            },
            'listeners' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(Listener::class)
                    ? $reflectionClass->name
                    : null;
            },
            'controllers' => static function (ReflectionClass $reflectionClass) {
                /** @var class-string $className */
                $className = $reflectionClass->name;

                return $reflectionClass->implementsInterface(ControllerCommand::class)
                    ? $className::__controller()
                    : null;
            },
            'descriptions' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(EventEngineDescription::class)
                    ? $reflectionClass->name
                    : null;
            },
            'child_repositories' => static function (ReflectionClass $reflectionClass) {
                $parentReflection = $reflectionClass->getParentClass();

                return $parentReflection && $parentReflection->name === Repository::class
                    ? $reflectionClass->name
                    : null;
            },
            'types' => static function (ReflectionClass $reflectionClass) {
                return $reflectionClass->implementsInterface(Type::class)
                    ? $reflectionClass->name
                    : null;
            },
        ];

        /** @var ?Definition $eventQueueDefinition */
        $eventQueueDefinition = null;
        foreach ($resources as $resourceReflectionClass) {
            if (! $resourceReflectionClass->implementsInterface(MessageProducer::class)) {
                continue;
            }

            if ($eventQueueDefinition instanceof Definition) {
                throw new Exception('You can only have 1 event queue.');
            }

            if ($container->hasDefinition($resourceReflectionClass->name)) {
                $eventQueueDefinition = $container->getDefinition($resourceReflectionClass->name);
            } else {
                $eventQueueDefinition = new Definition($resourceReflectionClass->name);
            }
        }

        if ($eventQueueDefinition instanceof Definition) {
            $container->setDefinition('event_engine.event_queue', $eventQueueDefinition);
        }

        foreach ($mappers as $name => $mapper) {
            $container->setParameter(
                sprintf('event_engine.%s', $name),
                array_unique(array_filter(array_map($mapper, $resources)))
            );
        }

        $this->buildRepositories($container);
        $this->makePublic($container);
    }

    private function buildRepositories(ContainerBuilder $container): void
    {
        $repository = $container->getDefinition(Repository::class);
        $childRepositories = $container->getParameter('event_engine.child_repositories');
        $aggregates = $container->getParameter('event_engine.aggregates');
        $entityNamespace = $container->getParameter('event_engine.entity_namespace');

        $definitions = array_reduce(
            $aggregates,
            static function (array $result, $aggregate) use ($entityNamespace, $repository) {
                $reflectionClass = new ReflectionClass($aggregate);
                $aggregate = $reflectionClass->getShortName();

                $key = sprintf('event_engine.repository.%s', StringUtil::decamilize($aggregate));

                $result[$key] = (new Definition(
                    $repository->getClass(),
                    [
                        new Reference(DocumentStore::class),
                        EventEngineUtil::fromAggregateNameToDocumentStoreName($aggregate),
                        EventEngineUtil::fromAggregateNameToStateClass($aggregate, $entityNamespace),
                        new Reference('event_engine.connection'),
                    ]
                ))
                    ->setPublic(true);

                return $result;
            },
            [],
        );

        $container->addDefinitions($definitions);
        $container->removeDefinition(Repository::class);

        foreach ($childRepositories as $childRepository) {
            preg_match_all('/\\\([^\\\]+)Repository$/', $childRepository, $matches);

            $container->getDefinition($childRepository)
                ->setArguments(
                    $container
                        ->getDefinition(
                            sprintf(
                                'event_engine.repository.%s',
                                strtolower(StringUtil::decamilize($matches[1][0]))
                            )
                        )
                        ->getArguments()
                )
                ->setPublic(true);
        }
    }

    private function makePublic(ContainerBuilder $container): void
    {
        $classes = [
            ...$container->getParameter('event_engine.resolvers'),
            ...$container->getParameter('event_engine.listeners'),
            ...$container->getParameter('event_engine.pre_processors'),
            ...$container->getParameter('event_engine.controllers'),
        ];

        foreach ($classes as $class) {
            if (! $container->hasDefinition($class)) {
                throw new RuntimeException(
                    sprintf(
                        'Class \'%s\' can\'t be made public because it\'s not found.',
                        $class
                    )
                );
            }

            $container->getDefinition($class)->setPublic(true);
        }
    }
}
