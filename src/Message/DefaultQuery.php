<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use function count;
use function explode;
use function implode;

trait DefaultQuery
{
    /**
     * @inheritDoc
     */
    public static function __resolver()
    {
        $parts = explode('\\', static::class);

        $parts[count($parts) - 2] = $parts[count($parts) - 2] === 'Query' ? 'Resolver' : $parts[count($parts) - 2];

        return implode('\\', $parts);
    }

//    /**
//     * @return array<int, ResponseTypeSchema>
//     */
//    public static function __responseSchemasPerStatusCode() : array
//    {
//        return [
//            static::__defaultStatusCode() => JsonSchema::object([]),
//        ];
//    }
//
//    public static function __defaultStatusCode() : int
//    {
//        return Response::HTTP_OK;
//    }
//
//    public static function __defaultResponseSchema() : ResponseTypeSchema
//    {
//        return static::__responseSchemasPerStatusCode()[static::__defaultStatusCode()];
//    }
}
