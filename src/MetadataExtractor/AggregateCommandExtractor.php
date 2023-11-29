<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand as AggregateCommandAttribute;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;

class AggregateCommandExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /** @param ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
    public function newFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        /** @var bool $newAggregate */
        $newAggregate = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                AggregateCommandAttribute::class => static fn (
                    AggregateCommandAttribute $attribute,
                ) => $attribute->newAggregate(),
                /** @param class-string<AggregateCommand> $class */
                AggregateCommand::class => static fn (string $class) => $class::__newAggregate(),
            ],
        );

        return $newAggregate;
    }

    /** @param ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
    public function aggregateMethodFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var string $aggregateMethod */
        $aggregateMethod = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                AggregateCommandAttribute::class => static fn (
                    AggregateCommandAttribute $attribute,
                ) => $attribute->aggregateMethod(),
                /** @param class-string<AggregateCommand> $class */
                AggregateCommand::class => static fn (string $class) => $class::__aggregateMethod(),
            ],
        );

        return $aggregateMethod;
    }

    public function aggregateIdFromAggregateCommand(JsonSchemaAwareRecord $aggregateCommand): string|null
    {
        /** @var string|null $aggregateId */
        $aggregateId = $this->metadataExtractor->metadataFromInstance(
            $aggregateCommand,
            [
                AggregateCommandAttribute::class => static fn (
                    AggregateCommandAttribute $attribute,
                    JsonSchemaAwareRecord $aggregateCommand,
                ) => $aggregateCommand->toArray()[$attribute->aggregateIdProperty()],
                /** @param class-string<AggregateCommand> $class */
                AggregateCommand::class => static fn (
                    AggregateCommand $aggregateCommand,
                ) => $aggregateCommand->__aggregateId(),
            ],
        );

        return $aggregateId;
    }
}
