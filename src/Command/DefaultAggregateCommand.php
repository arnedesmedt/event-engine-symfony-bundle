<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

use ADS\Bundle\EventEngineBundle\Request\DefaultsAreNotRequired;
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
}
