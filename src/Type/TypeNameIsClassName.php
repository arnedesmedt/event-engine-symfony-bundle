<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Type;

use ReflectionClass;

trait TypeNameIsClassName
{
    public static function __type(): string
    {
        return (new ReflectionClass(static::class))->getShortName();
    }
}
