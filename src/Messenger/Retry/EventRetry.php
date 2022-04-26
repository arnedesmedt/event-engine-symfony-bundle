<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Retry;

use EventEngine\Messaging\MessageBag;

class EventRetry extends Retry
{
    protected function messageBagAllowed(MessageBag $messageBag): bool
    {
        return $messageBag->messageType() === MessageBag::TYPE_EVENT;
    }
}