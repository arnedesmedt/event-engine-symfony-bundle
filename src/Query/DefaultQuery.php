<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

use ADS\Bundle\EventEngineBundle\Exception\MessageException;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

use function class_exists;
use function str_replace;
use function substr_count;

trait DefaultQuery
{
    use JsonSchemaAwareRecordLogic;

    public static function __resolver(): string
    {
        if (substr_count(static::class, '\\Query\\') > 1) {
            throw MessageException::nestedMessageFolder(static::class, 'Query');
        }

        $resolverClass = str_replace('\\Query\\', '\\Resolver\\', static::class);

        if (! class_exists($resolverClass)) {
            throw MessageException::noHandlerFound(static::class, 'resolver');
        }

        return $resolverClass;
    }
}
