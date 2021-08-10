<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

use ADS\JsonImmutableObjects\DefaultsAreNotRequired;
use ReflectionClass;

trait DefaultAggregateCommand
{
    use DefaultsAreNotRequired;

    /**
     * The default aggregate method is the shortname of the class.
     */
    public static function __aggregateMethod(): string
    {
        return (new ReflectionClass(static::class))->getShortName();
    }

    public static function __newAggregate(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function __eventsToRecord(): array
    {
        return [];
    }
}
