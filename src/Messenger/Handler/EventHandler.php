<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use EventEngine\Messaging\MessageBag;

class EventHandler extends Handler
{
    public function __invoke(MessageBag $messageBag): mixed
    {
        return $this->dispatchMessageBag($messageBag);
    }
}
