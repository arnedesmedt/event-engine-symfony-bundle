<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Messenger\Message\QueryMessageWrapper;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[Deprecated]
#[AsMessageHandler(bus: 'query.bus', fromTransport: 'event_engine.query')]
class AsyncQueryHandler extends Handler
{
    public function __invoke(QueryMessageWrapper $messageWrapper): mixed
    {
        return $this->dispatchMessageWrapper($messageWrapper);
    }
}
