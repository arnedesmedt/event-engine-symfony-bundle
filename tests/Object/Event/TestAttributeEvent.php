<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Event;

use ADS\Bundle\EventEngineBundle\Attribute\Event;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use TeamBlue\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

#[Event(
    applyMethod: 'whenTestAttributeEventAdded',
)]
class TestAttributeEvent implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
