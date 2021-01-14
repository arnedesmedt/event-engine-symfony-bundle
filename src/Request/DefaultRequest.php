<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Request;

use ReflectionClass;

use function array_keys;

trait DefaultRequest
{
    /**
     * @return array<string>
     *
     * @inheritDoc
     */
    private static function __optionalProperties(): array
    {
        return array_keys(
            (new ReflectionClass(static::class))->getDefaultProperties()
        );
    }
}
