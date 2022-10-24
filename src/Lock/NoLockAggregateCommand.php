<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;

final class NoLockAggregateCommand
{
    public function __construct(
        private EventEngine $eventEngine,
    ) {
    }

    public function __invoke(MessageBag $messageBag): mixed
    {
        return $this->eventEngine->dispatch($messageBag);
    }
}
