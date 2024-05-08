<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Command;

use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Command\DefaultAggregateCommand;
use ADS\Bundle\EventEngineBundle\Messenger\DefaultQueueable;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestInterfaceEvent;

class TestInterfaceAggregateCommand implements AggregateCommand, Queueable
{
    use DefaultAggregateCommand;
    use DefaultQueueable;

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

    public static function __queue(): bool
    {
        return true;
    }

    public static function __maxRetries(): int
    {
        return 10;
    }

    public static function __delayInMilliseconds(): int
    {
        return 1000;
    }

    public static function __multiplier(): int
    {
        return 8;
    }

    public static function __maxDelayInMilliseconds(): int
    {
        return 10 * 60 * 1000;
    }

    public static function __sendToLinkedFailureTransport(): bool
    {
        return false;
    }
}
