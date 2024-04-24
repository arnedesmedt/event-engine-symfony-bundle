<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use ADS\Bundle\EventEngineBundle\Event\Event;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AggregateCommand extends Command
{
    /**
     * @param array<class-string<Event>> $eventsToRecord
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

    /** @return array<class-string<Event>> */
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
