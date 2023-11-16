<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\State;

use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

class TestState implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;

    public function test(): string
    {
        return $this->test;
    }
}
