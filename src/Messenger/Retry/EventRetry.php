<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Retry;

use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Message\Message;

class EventRetry extends Retry
{
    protected function messageAllowed(Message $message): bool
    {
        return $message instanceof Event;
    }
}
