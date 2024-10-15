<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand as AggregateCommandAttribute;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use EventEngine\Aggregate\ContextProvider;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

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
                ): bool => $attribute->newAggregate(),
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
                ): string => $attribute->aggregateMethod(),
                /** @param class-string<AggregateCommand> $class */
                AggregateCommand::class => static fn (string $class) => $class::__aggregateMethod(),
            ],
        );

        return $aggregateMethod;
    }

    /**
     * @param ReflectionClass<JsonSchemaAwareRecord> $reflectionClass
     *
     * @return array<class-string<ContextProvider>>
     */
    public function contextProvidersFromReflectionClass(ReflectionClass $reflectionClass): array
    {
        /** @var array<class-string<ContextProvider>> $contextProviders */
        $contextProviders = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                AggregateCommandAttribute::class => static fn (
                    AggregateCommandAttribute $attribute,
                ): array => $attribute->contextProviders(),
                /** @param class-string<AggregateCommand> $class */
                AggregateCommand::class => static fn (string $class) => $class::__contextProviders(),
            ],
        );

        return $contextProviders;
    }

    public function aggregateIdFromAggregateCommand(JsonSchemaAwareRecord $aggregateCommand): string|null
    {
        /** @var string $aggregateId */
        $aggregateId = $this->metadataExtractor->metadataFromInstance(
            $aggregateCommand,
            [
                AggregateCommandAttribute::class => static fn (
                    AggregateCommandAttribute $attribute,
                    JsonSchemaAwareRecord $aggregateCommand,
                ) => $aggregateCommand->toArray()[$attribute->aggregateIdProperty()],
                AggregateCommand::class => static fn (
                    AggregateCommand $aggregateCommand,
                ): string => $aggregateCommand->__aggregateId(),
            ],
        );

        if ($aggregateId === MetadataExtractor::METADATA_NOT_FOUND) {
            return null;
        }

        return $aggregateId;
    }
}
