<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Event;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

interface Listener
{
    /** @return array<class-string<JsonSchemaAwareRecord>>|class-string<JsonSchemaAwareRecord> */
    public static function __handleEvents(): array|string;
}
