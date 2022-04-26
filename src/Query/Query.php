<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

use EventEngine\Schema\TypeSchema;

interface Query
{
    /**
     * @return class-string|string
     */
    public static function __resolver(): string;

    /**
     * @return array<int, TypeSchema>
     */
    public static function __responseSchemasPerStatusCode(): array;

    public static function __responseSchemaForStatusCode(int $statusCode): TypeSchema;

    public static function __defaultStatusCode(): ?int;

    public static function __defaultResponseSchema(): TypeSchema;

    /**
     * @return array<string, TypeSchema>
     */
    public static function __extraResponse(): array;
}
