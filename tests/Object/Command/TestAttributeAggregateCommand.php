<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Command;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Attribute\Queueable;
use ADS\Bundle\EventEngineBundle\Tests\Object\ContextProvider\TestContextProvider;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestAttributeEvent;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use TeamBlue\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

#[AggregateCommand(
    aggregateIdProperty: 'test',
    aggregateMethod: 'attributeCommand',
    eventsToRecord: [
        TestAttributeEvent::class,
    ],
    contextProviders: [
        TestContextProvider::class,
    ],
    newAggregate: true,
)]
#[Queueable(
    queue: true,
    maxRetries: 10,
    delayInMilliseconds: 1000,
    multiplier: 8,
    maxDelayInMilliseconds: 10 * 60 * 1000,
    sendToLinkedFailureTransport: false,
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
