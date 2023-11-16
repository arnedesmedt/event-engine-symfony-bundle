<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Command;

use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Command\DefaultAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestInterfaceEvent;

class TestInterfaceAggregateCommand implements AggregateCommand
{
    use DefaultAggregateCommand;

    private string $test;

    public function __aggregateId(): string
    {
        return $this->test;
    }

    /** @inheritDoc */
    public static function __eventsToRecord(): array
    {
        return [
            TestInterfaceEvent::class,
        ];
    }
}
