<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Messenger\Message\EventMessageWrapper;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus', fromTransport: 'event_engine.event')]
class AsyncEventHandler extends Handler
{
    public function __invoke(EventMessageWrapper $messageWrapper): mixed
    {
        return $this->dispatchMessageWrapper($messageWrapper);
    }
}
