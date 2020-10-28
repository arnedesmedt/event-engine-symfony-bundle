<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Type;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

interface Type extends JsonSchemaAwareRecord
{
    public static function __typeName(): string;
}
