<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Type;

use ADS\Bundle\EventEngineBundle\Attribute\Type;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use TeamBlue\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

#[Type]
class TestAttributeType implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
