<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Command;

use ADS\Bundle\EventEngineBundle\Attribute\ControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Controller\TestController;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[ControllerCommand(controller: TestController::class)]
class TestAttributeControllerCommand implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
