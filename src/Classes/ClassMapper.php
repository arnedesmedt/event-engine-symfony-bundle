<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Classes;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
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
use function array_unique;
use function in_array;
use function is_a;
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
        private readonly array $commands,
        private readonly array $aggregateCommands,
        private readonly array $aggregates,
        private readonly array $preProcessors,
    ) {
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, class-string<AggregateRoot<JsonSchemaAwareRecord>>> */
    public function commandAggregateMapping(): array
    {
        if (! empty($this->commandAggregateMapping)) {
            return $this->commandAggregateMapping;
        }

        $this->mapForAggregates();

        return $this->commandAggregateMapping;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, array<class-string|string>> */
    public function commandServiceMapping(): array
    {
        if (! empty($this->commandServiceMapping)) {
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
                $firstParameter = array_shift($parameters);

                if (! $firstParameter) {
                    continue;
                }

                /** @var ReflectionNamedType|ReflectionUnionType|null $commandTypes */
                $commandTypes = $firstParameter->getType();
                $commandTypes = $commandTypes instanceof ReflectionUnionType
                    ? $commandTypes->getTypes()
                    : [$commandTypes];

                $commandTypes = array_filter(
                    $commandTypes,
                    fn ($type) => $type !== null && in_array($type->getName(), $this->aggregateCommands)
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

                foreach ($commandTypes as $commandType) {
                    /** @var class-string<JsonSchemaAwareRecord> $commandClass */
                    $commandClass = $commandType->getName();

                    if (is_a($commandClass, AggregateCommand::class, true)) {
                        // todo how to fix replace services with attribute?
                        $services = $commandClass::__replaceServices($services);
                    }

                    $this->commandAggregateMapping[$commandClass] = $aggregateClass;
                    $this->commandServiceMapping[$commandClass] = $services;
                }
            }
        }
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, array<class-string<JsonSchemaAwareRecord>>> */
    public function commandEventMapping(): array
    {
        if (! empty($this->commandEventMapping)) {
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
                fn (string $eventClass) => $this->aggregateRootClassFromEventClass($eventClass),
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
        if (! empty($this->commandPreProcessorMapping)) {
            return $this->commandPreProcessorMapping;
        }

        $mapping = [];
        foreach ($this->preProcessors as $preProcessorClass) {
            $preProcessorReflectionClass = new ReflectionClass($preProcessorClass);
            $priority = $this->preProcessorExtractor->priorityFromReflectionClass($preProcessorReflectionClass);
            $commandClasses = $this->commandExtractor->fromPreProcessorReflectionClass($preProcessorReflectionClass);

            if (empty($commandClasses)) {
                throw new RuntimeException(
                    sprintf(
                        'PreProcessor \'%s\' has no commands.',
                        $preProcessorReflectionClass->getName(),
                    ),
                );
            }

            foreach ($commandClasses as $commandClass) {
                if (! in_array($commandClass, $this->commands)) {
                    throw new RuntimeException(
                        sprintf(
                            'PreProcessor \'%s\' has command \'%s\' which is not a command.',
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
                static fn (array $a, array $b) => $a['priority'] <=> $b['priority'],
            );
        }

        $this->commandPreProcessorMapping = array_map(
            static fn (array $preProcessors) => array_map(
                static fn (array $preProcessor) => $preProcessor['preProcessor'],
                $preProcessors,
            ),
            $mapping,
        );

        return $this->commandPreProcessorMapping;
    }

    /** @return array<class-string<AggregateRoot<JsonSchemaAwareRecord>>, string> */
    public function aggregateIdentifierMapping(): array
    {
        if (! empty($this->aggregateIdentifierMapping)) {
            return $this->aggregateIdentifierMapping;
        }

        foreach ($this->aggregates as $aggregateClass) {
            $this->aggregateIdentifierMapping[$aggregateClass] = $aggregateClass::aggregateIdPropertyName();
        }

        return $this->aggregateIdentifierMapping;
    }
}
