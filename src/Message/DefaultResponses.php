<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use ADS\Bundle\EventEngineBundle\Exception\ResponseException;
use EventEngine\Schema\TypeSchema;

use function reset;

/**
 * @method static array __responseSchemasPerStatusCode()
 */
trait DefaultResponses
{
    public static function __responseSchemaForStatusCode(int $statusCode): TypeSchema
    {
        $responses = self::__responseSchemasPerStatusCode();

        if (! isset($responses[$statusCode])) {
            throw ResponseException::statusCodeNotFound($statusCode, static::class);
        }

        return $responses[$statusCode];
    }

    public static function __defaultStatusCode(): ?int
    {
        return null;
    }

    public static function __defaultResponseSchema(): TypeSchema
    {
        $statusCode = self::__defaultStatusCode();
        $responses = self::__responseSchemasPerStatusCode();

        if ($statusCode === null) {
            return reset($responses);
        }

        if (! isset($responses[$statusCode])) {
            throw ResponseException::statusCodeNotFound($statusCode, static::class);
        }

        return $responses[$statusCode];
    }
}
