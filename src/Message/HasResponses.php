<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use EventEngine\Schema\TypeSchema;

interface HasResponses
{
    /**
     * @return array<int, TypeSchema>
     */
    public static function __responseSchemasPerStatusCode(): array;

    public static function __responseSchemaForStatusCode(int $statusCode): TypeSchema;

    public static function __defaultStatusCode(): ?int;

    public static function __defaultResponseSchema(): TypeSchema;
}
