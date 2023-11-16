<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Event;

use ADS\Bundle\EventEngineBundle\Attribute\Event;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Event(
    applyMethod: 'whenTestAttributeEventAdded', // @phpstan-ignore-line
)]
class TestAttributeEvent implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
