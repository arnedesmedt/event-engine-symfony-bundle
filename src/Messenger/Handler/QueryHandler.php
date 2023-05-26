<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use EventEngine\Messaging\MessageBag;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class QueryHandler extends Handler
{
    public function __invoke(MessageBag $messageBag): mixed
    {
        return $this->dispatchMessageBag($messageBag);
    }
}
