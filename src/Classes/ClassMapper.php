<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Classes;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\CommandExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\EventClassExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\PreProcessorExtractor;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_shift;
use function array_slice;
use function array_unique;
use function class_exists;
use function count;
use function in_array;
use function reset;
use function sprintf;
use function usort;

class ClassMapper
{
    /** @var array<class-string<JsonSchemaAwareRecord>, class-string<AggregateRoot<JsonSchemaAwareRecord>>> */
    private array $commandAggregateMapping = [];

    /** @var array<class-string<JsonSchemaAwareRecord>, array<class-string<JsonSchemaAwareRecord>>> */
    private array $commandEventMapping = [];

    /** @var array<class-string<JsonSchemaAwareRecord>, array<class-string|string>> */
    private array $commandServiceMapping = [];

    /** @var array<class-string<JsonSchemaAwareRecord>, array<class-string>> */
    private array $commandContextProviderMapping = [];

    /** @var array<class-string<JsonSchemaAwareRecord>, array<class-string>> */
    private array $commandPreProcessorMapping = [];

    /** @var array<class-string<AggregateRoot<JsonSchemaAwareRecord>>, string> */
    private array $aggregateIdentifierMapping = [];

    /**
     * @param array<class-string<JsonSchemaAwareRecord>> $commands
     * @param array<class-string<JsonSchemaAwareRecord>> $aggregateCommands
     * @param array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> $aggregates
     * @param array<class-string> $preProcessors
     */
    public function __construct(
        private readonly EventClassExtractor $eventClassExtractor,
        private readonly PreProcessorExtractor $preProcessorExtractor,
        private readonly CommandExtractor $commandExtractor,
        private readonly AggregateCommandExtractor $aggregateCommandExtractor,
        private readonly array $commands,
        private readonly array $aggregateCommands,
        private readonly array $aggregates,
        private readonly array $preProcessors,
    ) {
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, class-string<AggregateRoot<JsonSchemaAwareRecord>>> */
    public function commandAggregateMapping(): array
    {
        if ($this->commandAggregateMapping !== []) {
            return $this->commandAggregateMapping;
        }

        $this->mapForAggregates();

        return $this->commandAggregateMapping;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, array<class-string>> */
    public function commandContextProviderMapping(): array
    {
        if ($this->commandContextProviderMapping !== []) {
            return $this->commandContextProviderMapping;
        }

        $this->mapForAggregates();

        return $this->commandContextProviderMapping;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, array<class-string|string>> */
    public function commandServiceMapping(): array
    {
        if ($this->commandServiceMapping !== []) {
            return $this->commandServiceMapping;
        }

        $this->mapForAggregates();

        return $this->commandServiceMapping;
    }

    private function mapForAggregates(): void
    {
        foreach ($this->aggregates as $aggregateClass) {
            $aggregateReflectionClass = new ReflectionClass($aggregateClass);
            $publicAggregateMethods = $aggregateReflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($publicAggregateMethods as $publicAggregateMethod) {
                $parameters = $publicAggregateMethod->getParameters();
                $command = array_shift($parameters);

                if (! $command) {
                    continue;
                }

                /** @var ReflectionNamedType|ReflectionUnionType|null $commandType */
                $commandType = $command->getType();
                /** @var array<ReflectionNamedType|null> $commandTypes */
                $commandTypes = $commandType instanceof ReflectionUnionType
                    ? $commandType->getTypes()
                    : [$commandType];

                /** @var ReflectionNamedType|null $commandType */
                $commandType = reset($commandTypes);
                /** @var class-string<JsonSchemaAwareRecord>|null $commandClass */
                $commandClass = $commandType?->getName();
                if ($commandClass === null) {
                    continue;
                }

                if (! class_exists($commandClass)) {
                    continue;
                }

                $commandReflection = new ReflectionClass($commandClass);

                if (! $this->commandExtractor->isCommandFromReflectionClass($commandReflection)) {
                    continue;
                }

                $commandTypes = array_filter(
                    $commandTypes,
                    fn ($type): bool => $type instanceof ReflectionNamedType
                        && in_array($type->getName(), $this->aggregateCommands)
                );

                /** @var array<class-string|string> $services */
                $services = array_filter(
                    array_map(
                        static function (ReflectionParameter $parameter) {
                            /** @var ReflectionNamedType|null $type */
                            $type = $parameter->getType();

                            return $type ? $type->getName() : null;
                        },
                        $parameters,
                    ),
                );

                $contextProviders = $this->aggregateCommandExtractor
                    ->contextProvidersFromReflectionClass($commandReflection);
                $services = array_slice($services, count($contextProviders));

                foreach ($commandTypes as $commandType) {
                    /** @var class-string<JsonSchemaAwareRecord> $commandClass */
                    $commandClass = $commandType->getName();

                    $this->commandAggregateMapping[$commandClass] = $aggregateClass;
                    $this->commandContextProviderMapping[$commandClass] = $contextProviders;
                    $this->commandServiceMapping[$commandClass] = $services;
                }
            }
        }
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, array<class-string<JsonSchemaAwareRecord>>> */
    public function commandEventMapping(): array
    {
        if ($this->commandEventMapping !== []) {
            return $this->commandEventMapping;
        }

        foreach ($this->aggregateCommands as $aggregateCommandClass) {
            $events = $this
                ->eventClassExtractor
                ->fromAggregateCommandReflectionClass(new ReflectionClass($aggregateCommandClass));

            $this->commandEventMapping[$aggregateCommandClass] = $events;
        }

        return $this->commandEventMapping;
    }

    /**
     * @param array<class-string<JsonSchemaAwareRecord>> $eventClasses
     *
     * @return array<class-string<AggregateRoot<JsonSchemaAwareRecord>>>
     */
    public function aggregateRootClassesFromEventClasses(array $eventClasses): array
    {
        return array_unique(
            array_map(
                /** @param class-string<JsonSchemaAwareRecord> $eventClass */
                fn (string $eventClass): string => $this->aggregateRootClassFromEventClass($eventClass),
                $eventClasses,
            ),
        );
    }

    /**
     * @param class-string<JsonSchemaAwareRecord> $eventClass
     *
     * @return class-string<AggregateRoot<JsonSchemaAwareRecord>>
     */
    private function aggregateRootClassFromEventClass(string $eventClass): string
    {
        $commandAggregateMapping = $this->commandAggregateMapping();

        foreach ($this->commandEventMapping() as $commandClass => $eventClasses) {
            if (
                ! (
                    array_key_exists($commandClass, $commandAggregateMapping)
                    && in_array($eventClass, $eventClasses))
            ) {
                continue;
            }

            return $commandAggregateMapping[$commandClass];
        }

        throw new RuntimeException(sprintf('Unable to find aggregate for event %s', $eventClass));
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, array<class-string>> */
    public function commandPreProcessorMapping(): array
    {
        if ($this->commandPreProcessorMapping !== []) {
            return $this->commandPreProcessorMapping;
        }

        $mapping = [];
        foreach ($this->preProcessors as $preProcessorClass) {
            $preProcessorReflectionClass = new ReflectionClass($preProcessorClass);
            $priority = $this->preProcessorExtractor->priorityFromReflectionClass($preProcessorReflectionClass);
            $commandClasses = $this->commandExtractor->fromPreProcessorReflectionClass($preProcessorReflectionClass);

            if ($commandClasses === []) {
                throw new RuntimeException(
                    sprintf(
                        "PreProcessor '%s' has no commands.",
                        $preProcessorReflectionClass->getName(),
                    ),
                );
            }

            foreach ($commandClasses as $commandClass) {
                if (! in_array($commandClass, $this->commands)) {
                    throw new RuntimeException(
                        sprintf(
                            "PreProcessor '%s' has command '%s' which is not a command.",
                            $preProcessorReflectionClass->getName(),
                            $commandClass,
                        ),
                    );
                }

                if (! isset($mapping[$commandClass])) {
                    $mapping[$commandClass] = [];
                }

                $mapping[$commandClass][] = [
                    'priority' => $priority,
                    'preProcessor' => $preProcessorClass,
                ];
            }
        }

        foreach ($mapping as &$preProcessors) {
            usort(
                $preProcessors,
                static fn (array $a, array $b): int => $a['priority'] <=> $b['priority'],
            );
        }

        $this->commandPreProcessorMapping = array_map(
            static fn (array $preProcessors): array => array_map(
                static fn (array $preProcessor): string => $preProcessor['preProcessor'],
                $preProcessors,
            ),
            $mapping,
        );

        return $this->commandPreProcessorMapping;
    }

    /** @return array<class-string<AggregateRoot<JsonSchemaAwareRecord>>, string> */
    public function aggregateIdentifierMapping(): array
    {
        if ($this->aggregateIdentifierMapping !== []) {
            return $this->aggregateIdentifierMapping;
        }

        foreach ($this->aggregates as $aggregateClass) {
            $this->aggregateIdentifierMapping[$aggregateClass] = $aggregateClass::aggregateIdPropertyName();
        }

        return $this->aggregateIdentifierMapping;
    }
}
