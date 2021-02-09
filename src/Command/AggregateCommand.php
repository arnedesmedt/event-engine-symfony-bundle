<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

use ADS\Bundle\EventEngineBundle\Event\Event;

interface AggregateCommand extends Command
{
    public function __aggregateId(): string;

    public static function __aggregateMethod(): string;

    public static function __newAggregate(): bool;

    /**
     * @return array<class-string<Event>>
     */
    public static function __eventsToRecord(): array;
}
