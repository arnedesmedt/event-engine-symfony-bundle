<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection\Compiler;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ControllerExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResolverExtractor;
use ADS\Bundle\EventEngineBundle\Repository\Repository;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\Util\MetadataExtractor\AttributeExtractor;
use ADS\Util\MetadataExtractor\ClassExtractor;
use ADS\Util\MetadataExtractor\InstanceExtractor;
use ADS\Util\MetadataExtractor\MetadataExtractor;
use ADS\Util\StringUtil;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

use function array_map;
use function array_reduce;
use function preg_match_all;
use function sprintf;
use function strtolower;

final class EventEnginePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->buildAggregateRepositories($container);
        $this->buildRepositories($container);
        $container->removeDefinition(Repository::class);
        $this->makeDependenciesPublic($container);
    }

    private function buildAggregateRepositories(ContainerBuilder $container): void
    {
        /** @var array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> $aggregates */
        $aggregates = $container->getParameter('event_engine.aggregates');
        /** @var string $entityNamespace */
        $entityNamespace = $container->getParameter('event_engine.entity_namespace');

        $aggregateRepositoryDefinitions = array_reduce(
            $aggregates,
            static function (array $result, $aggregateClass) use ($entityNamespace) {
                $aggregateShortName = (new ReflectionClass($aggregateClass))->getShortName();
                $identifier = sprintf('event_engine.repository.%s', StringUtil::decamelize($aggregateShortName));

                $result[$identifier] = (new Definition(
                    Repository::class,
                    [
                        new Reference(DocumentStore::class),
                        EventEngineUtil::fromAggregateNameToDocumentStoreName($aggregateShortName),
                        EventEngineUtil::fromAggregateNameToStateClass($aggregateShortName, $entityNamespace),
                        EventEngineUtil::fromAggregateNameToStatesClass($aggregateShortName, $entityNamespace),
                    ],
                ))
                    ->setPublic(true);

                return $result;
            },
            [],
        );

        $container->addDefinitions($aggregateRepositoryDefinitions);
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
                    ],
                ))
                    ->setPublic(true);

                return $result;
            },
            [],
        );

        $container->addDefinitions($aggregateRepositoryDefinitions);

        foreach ($repositories as $repository) {
            preg_match_all('/\\\([^\\\]+)Repository$/', $repository, $matches);

            try {
                $repositoryDefinition = $container->getDefinition(
                    sprintf(
                        'event_engine.repository.%s',
                        strtolower(StringUtil::decamelize($matches[1][0])),
                    ),
                );
            } catch (ServiceNotFoundException) {
                continue;
            }

            $container->getDefinition($repository)
                ->setArguments($repositoryDefinition->getArguments())
                ->setPublic(true);
        }
    }

    private function makeDependenciesPublic(ContainerBuilder $container): void
    {
        $metadataExtractor = new MetadataExtractor(
            new AttributeExtractor(),
            new ClassExtractor(),
            new InstanceExtractor(),
        );
        $controllerExtractor = new ControllerExtractor($metadataExtractor);
        $resolverExtractor = new ResolverExtractor($metadataExtractor);

        /** @var array<class-string> $listeners */
        $listeners = $container->getParameter('event_engine.listeners');
        /** @var array<class-string> $preProcessors */
        $preProcessors = $container->getParameter('event_engine.pre_processors');
        /** @var array<class-string> $projectors */
        $projectors = $container->getParameter('event_engine.projectors');
        /** @var array<class-string<JsonSchemaAwareRecord>> $controllerCommands */
        $controllerCommands = $container->getParameter('event_engine.controller_commands');
        /** @var array<class-string<JsonSchemaAwareRecord>> $queries */
        $queries = $container->getParameter('event_engine.queries');

        $servicesToMakePublic = [
            ...$listeners,
            ...$preProcessors,
            ...$projectors,
            ...array_map(
                static fn (string $controllerCommandClass) => $controllerExtractor
                    ->fromReflectionClass(new ReflectionClass($controllerCommandClass)),
                $controllerCommands,
            ),
            ...array_map(
                static fn (string $queryClass) => $resolverExtractor
                    ->fromReflectionClass(new ReflectionClass($queryClass)),
                $queries,
            ),
        ];

        foreach ($servicesToMakePublic as $serviceToMakePublic) {
            if (! $container->hasDefinition($serviceToMakePublic)) {
                throw new RuntimeException(
                    sprintf(
                        'Service \'%s\' can\'t be made public because it\'s not found.',
                        $serviceToMakePublic,
                    ),
                );
            }

            $container->getDefinition($serviceToMakePublic)->setPublic(true);
        }
    }
}
