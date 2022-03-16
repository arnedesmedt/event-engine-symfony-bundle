<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message\Handler;

use EventEngine\EventEngine;
use EventEngine\Messaging\Message;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class MessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EventEngine $eventEngine
    ) {
    }

    public function __invoke(Message $message): mixed
    {
        return $this->eventEngine->dispatch($message);
    }
}
