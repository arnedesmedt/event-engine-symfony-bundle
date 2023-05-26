<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use EventEngine\EventEngine;
use EventEngine\Messaging\Message;

final class NoLockAggregate implements LockAggregateStrategy
{
    public function __construct(
        private EventEngine $eventEngine,
    ) {
    }

    public function __invoke(Message $message): mixed
    {
        return $this->eventEngine->dispatch($message);
    }
}
