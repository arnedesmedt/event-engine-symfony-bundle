<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Response;

use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

class Ok implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $id;
}
