<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection\Compiler;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Event\Listener;
use ADS\Bundle\EventEngineBundle\Message\Command;
use ADS\Bundle\EventEngineBundle\Message\Event;
use ADS\Bundle\EventEngineBundle\Message\Query;
use ADS\Bundle\EventEngineBundle\Repository\Repository;
use ADS\Bundle\EventEngineBundle\Util;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\EventEngineDescription;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function array_filter;
use function array_reduce;
use function preg_match_all;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;

final class EventEnginePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        $resources = $container->getResources();

        $domainNamespace = $container->getParameter('event_engine.domain_namespace');
        $filter = sprintf('reflection.%s', $domainNamespace);

        $resources = array_filter(
            $resources,
            static function ($resource) use ($filter) {
                return strpos($resource . '', $filter) === 0;
            }
        );

        [
            $commandClasses,
            $queryClasses,
            $resolverClasses,
            $eventClasses,
            $listenerClasses,
            $descriptionClasses,
            $aggregateShortNames,
            $repositories,
        ] = array_reduce(
            $resources,
            static function (array $classes, $reflectionClass) {
                /** @var class-string $class */
                $class = substr($reflectionClass . '', 11);
                $reflectionClass = new ReflectionClass($class);

                if ($reflectionClass->implementsInterface(Command::class)) {
                    $classes[0][] = $class;

                    return $classes;
                }

                if ($reflectionClass->implementsInterface(Query::class)) {
                    $classes[1][] = $class;
                    $classes[2][] = $class::__resolver();

                    return $classes;
                }

                if ($reflectionClass->implementsInterface(Event::class)) {
                    $classes[3][] = $class;
                }

                if ($reflectionClass->implementsInterface(Listener::class)) {
                    $classes[4][] = $class;
                }

                if ($reflectionClass->implementsInterface(EventEngineDescription::class)) {
                    $classes[5][] = $class;
                }

                if ($reflectionClass->implementsInterface(AggregateRoot::class)) {
                    $classes[6][] = strtolower($reflectionClass->getShortName());
                }

                $parentReflection = $reflectionClass->getParentClass();
                if ($parentReflection && $parentReflection->getName() === Repository::class) {
                    $classes[7][] = $class;
                }

                return $classes;
            },
            [
                [],
                [],
                [],
                [],
                [],
                [],
                [],
                [],
            ]
        );

        $container->setParameter(
            'event_engine.commands',
            $commandClasses
        );

        $container->setParameter(
            'event_engine.queries',
            $queryClasses
        );

        $container->setParameter(
            'event_engine.events',
            $eventClasses
        );

        $container->setParameter(
            'event_engine.listeners',
            $eventClasses
        );

        $container->setParameter(
            'event_engine.descriptions',
            $descriptionClasses
        );

        $container->setParameter(
            'event_engine.aggregates',
            $aggregateShortNames
        );

        $container->setParameter(
            'event_engine.child_repositories',
            $repositories
        );

        $this->buildRepositories($container);
        $this->makePublic(
            $container,
            ...$resolverClasses,
            ...$listenerClasses
        );
    }

    private function buildRepositories(ContainerBuilder $container) : void
    {
        $repository = $container->getDefinition(Repository::class);
        $childRepositories = $container->getParameter('event_engine.child_repositories');
        $aggregates = $container->getParameter('event_engine.aggregates');
        $entityNamespace = $container->getParameter('event_engine.entity_namespace');

        $definitions = array_reduce(
            $aggregates,
            static function (array $result, string $aggregate) use ($entityNamespace, $repository) {
                $result[sprintf('event_engine.repository.%s', $aggregate)] = (new Definition(
                    $repository->getClass(),
                    [
                        new Reference(DocumentStore::class),
                        Util::fromAggregateNameToDocumentStoreName($aggregate),
                        Util::fromAggregateNameToStateClass($aggregate, $entityNamespace),
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
                                strtolower($matches[1][0])
                            )
                        )
                        ->getArguments()
                )
                ->setPublic(true);
        }
    }

    private function makePublic(ContainerBuilder $container, string ...$classes) : void
    {
        foreach ($classes as $class) {
            if (! $container->hasDefinition($class)) {
                throw new RuntimeException(
                    sprintf(
                        'Class \'%s\' can\'t be made public because it\'s not found.',
                        $class
                    )
                );
            }

            $container->getDefinition($class)
                ->setPublic(true);
        }
    }
}
