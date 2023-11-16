<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Response;

use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

class NotFound implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $message;
}
