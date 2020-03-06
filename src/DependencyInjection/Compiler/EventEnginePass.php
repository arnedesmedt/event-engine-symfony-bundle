<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection\Compiler;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Message\Command;
use ADS\Bundle\EventEngineBundle\Message\Event;
use ADS\Bundle\EventEngineBundle\Message\Query;
use ADS\Bundle\EventEngineBundle\Repository\Repository;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\EventEngineDescription;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use function array_filter;
use function array_reduce;
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

        [$commandClasses, $queryClasses, $eventClasses, $descriptionClasses, $aggregateShortNames] = array_reduce(
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

                    return $classes;
                }

                if ($reflectionClass->implementsInterface(Event::class)) {
                    $classes[2][] = $class;
                }

                if ($reflectionClass->implementsInterface(EventEngineDescription::class)) {
                    $classes[3][] = $class;
                }

                if ($reflectionClass->implementsInterface(AggregateRoot::class)) {
                    $classes[4][] = strtolower($reflectionClass->getShortName());
                }

                return $classes;
            },
            [
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
            'event_engine.descriptions',
            $descriptionClasses
        );

        $container->setParameter(
            'event_engine.aggregates',
            $aggregateShortNames
        );

        $this->buildRepositories($container);
    }

    private function buildRepositories(ContainerBuilder $container) : void
    {
        $repository = $container->getDefinition(Repository::class);
        $aggregates = $container->getParameter('event_engine.aggregates');
        $entityNamespace = $container->getParameter('event_engine.entity_namespace');

        $definitions = array_reduce(
            $aggregates,
            static function (array $result, string $aggregate) use ($entityNamespace, $repository) {
                $result[sprintf('event_engine.repository.%s', $aggregate)] = new Definition(
                    $repository->getClass(),
                    [
                        sprintf('@%s', DocumentStore::class),
                        $aggregate,
                        $entityNamespace,
                    ]
                );

                return $result;
            },
            [],
        );

        $container->addDefinitions($definitions);
        $container->removeDefinition(Repository::class);
    }
}
