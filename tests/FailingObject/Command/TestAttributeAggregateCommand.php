<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\FailingObject\Command;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand;

#[AggregateCommand(
    aggregateIdProperty: 'test',
    aggregateMethod: 'test',
    newAggregate: true,
    eventsToRecord: [],
)]
class TestAttributeAggregateCommand
{
    private string $test; // @phpstan-ignore-line

    public function test(): string
    {
        return $this->test;
    }
}
