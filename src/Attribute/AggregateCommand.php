<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Attribute(Attribute::TARGET_CLASS)]
class AggregateCommand extends Command
{
    /**
     * @param array<class-string<JsonSchemaAwareRecord>> $eventsToRecord
     * @param array<class-string> $contextProviders
     */
    public function __construct(
        private readonly string $aggregateIdProperty,
        private readonly string $aggregateMethod,
        private readonly array $eventsToRecord = [],
        private readonly array $contextProviders = [],
        private readonly bool $newAggregate = false,
    ) {
    }

    public function aggregateIdProperty(): string
    {
        return $this->aggregateIdProperty;
    }

    public function aggregateMethod(): string
    {
        return $this->aggregateMethod;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function eventsToRecord(): array
    {
        return $this->eventsToRecord;
    }

    /** @return array<class-string> */
    public function contextProviders(): array
    {
        return $this->contextProviders;
    }

    public function newAggregate(): bool
    {
        return $this->newAggregate;
    }
}
