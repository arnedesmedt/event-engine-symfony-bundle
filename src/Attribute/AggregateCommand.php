<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use ADS\Bundle\EventEngineBundle\Event\Event;
use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Attribute(Attribute::TARGET_CLASS)]
class AggregateCommand extends Command
{
    /** @param array<class-string<Event>> $eventsToRecord */
    public function __construct(
        private readonly string $aggregateIdProperty,
        private readonly string $aggregateMethod,
        private readonly bool $newAggregate = false,
        private readonly array $eventsToRecord = [],
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

    public function newAggregate(): bool
    {
        return $this->newAggregate;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function eventsToRecord(): array
    {
        return $this->eventsToRecord;
    }
}
