<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

use ADS\Bundle\EventEngineBundle\Request\DefaultsAreNotRequired;

use function count;
use function explode;
use function implode;

trait DefaultQuery
{
    use DefaultsAreNotRequired;

    public static function __resolver(): string
    {
        $parts = explode('\\', static::class);

        $parts[count($parts) - 2] = $parts[count($parts) - 2] === 'Query' ? 'Resolver' : $parts[count($parts) - 2];

        return implode('\\', $parts);
    }
}
