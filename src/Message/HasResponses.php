<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use EventEngine\Schema\ResponseTypeSchema;

interface HasResponses
{
    /**
     * @return array<int, ResponseTypeSchema>
     */
    public static function __responseSchemasPerStatusCode() : array;

    public static function __responseSchemaForStatusCode(int $statusCode) : ResponseTypeSchema;

    public static function __defaultStatusCode() : ?int;

    public static function __defaultResponseSchema() : ResponseTypeSchema;
}
