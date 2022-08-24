<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Response;

use EventEngine\Schema\TypeSchema;

interface HasResponses
{
    /**
     * @return array<int, TypeSchema>
     */
    public static function __responseSchemasPerStatusCode(): array;

    /**
     * @return array<int, class-string>
     */
    public static function __responseClassesPerStatusCode(): array;

    public static function __responseSchemaForStatusCode(int $statusCode): TypeSchema;

    public static function __defaultStatusCode(): ?int;

    public static function __defaultResponseSchema(): TypeSchema;
}
