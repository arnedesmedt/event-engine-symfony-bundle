<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Type;

use ReflectionClass;

trait TypeNameIsEntityAndClassName
{
    public static function __typeName(): string
    {
        return (new ReflectionClass(static::class))->getShortName();
    }
}
