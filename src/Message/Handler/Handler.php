<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message\Handler;

use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;

abstract class Handler
{
    public function __construct(
        private EventEngine $eventEngine
    ) {
    }

    public function __invoke(MessageBag $message): mixed
    {
        return $this->eventEngine->dispatch($message);
    }
}
