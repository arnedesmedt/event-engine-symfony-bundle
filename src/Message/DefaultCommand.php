<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use ReflectionClass;

trait DefaultCommand
{
    /**
     * The default aggregate method is the shortname of the class.
     */
    public static function __aggregateMethod() : string
    {
        $reflectionClass = new ReflectionClass(static::class);

        return $reflectionClass->getShortName();
    }
}
