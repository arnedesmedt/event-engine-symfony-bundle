<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Command;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestAttributeEvent;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[AggregateCommand(
    aggregateIdProperty: 'test',
    aggregateMethod: 'attributeCommand',
    newAggregate: true,
    eventsToRecord: [ // @phpstan-ignore-line
        TestAttributeEvent::class,
    ],
)]
class TestAttributeAggregateCommand implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;

    public function test(): string
    {
        return $this->test;
    }
}
