<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Response;

use EventEngine\Schema\TypeSchema;

interface HasResponses
{
    /**
     * @return array<int, class-string<TypeSchema>>
     */
    public static function __responseClassesPerStatusCode(): array;

    /**
     * @return class-string<TypeSchema>
     */
    public static function __responseClassForStatusCode(int $statusCode): string;

    /**
     * @return class-string<TypeSchema>
     */
    public static function __defaultResponseClass(): string;

    public static function __defaultStatusCode(): ?int;
}
