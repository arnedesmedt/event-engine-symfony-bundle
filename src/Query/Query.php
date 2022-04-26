<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

use ADS\Bundle\EventEngineBundle\Response\HasResponses;
use EventEngine\Schema\TypeSchema;

interface Query extends HasResponses
{
    /**
     * @return class-string|string
     */
    public static function __resolver(): string;

    /**
     * @return array<string, TypeSchema>
     */
    public static function __extraResponse(): array;
}
