<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use ADS\Bundle\EventEngineBundle\Exception\ResponseException;
use EventEngine\Schema\ResponseTypeSchema;

/**
 * @method static array __responseSchemasPerStatusCode()
 */
trait DefaultResponses
{
    public static function __responseSchemaForStatusCode(int $statusCode) : ResponseTypeSchema
    {
        $responses = self::__responseSchemasPerStatusCode();

        if (! isset($responses[$statusCode])) {
            throw ResponseException::statusCodeNotFound($statusCode, static::class);
        }

        return $responses[$statusCode];
    }

    public static function __defaultStatusCode() : ?int
    {
        return null;
    }
}
