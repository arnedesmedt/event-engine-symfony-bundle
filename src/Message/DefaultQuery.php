<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use function count;
use function explode;
use function implode;

trait DefaultQuery
{
    use DefaultResponses;

    /**
     * @inheritDoc
     */
    public static function __resolver()
    {
        $parts = explode('\\', static::class);

        $parts[count($parts) - 2] = $parts[count($parts) - 2] === 'Query' ? 'Resolver' : $parts[count($parts) - 2];

        return implode('\\', $parts);
    }
}
