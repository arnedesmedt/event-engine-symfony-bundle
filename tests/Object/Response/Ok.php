<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Response;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use TeamBlue\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

class Ok implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $id;
}
