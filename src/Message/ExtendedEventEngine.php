<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\MessageBusInterface;

final class ExtendedEventEngine implements MessageProducer
{
    public function __construct(
        private Flavour $flavour,
        private MessageBusInterface $commandBus,
        private MessageBusInterface $eventBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    public function produce(Message $message): mixed
    {
        $transferableMessage = $this->flavour->prepareNetworkTransmission($message);

        return match ($message->messageType()) {
            Message::TYPE_COMMAND => $this->commandBus->dispatch($transferableMessage),
            Message::TYPE_EVENT => $this->eventBus->dispatch($transferableMessage),
            default => $this->queryBus->dispatch($transferableMessage),
        };
    }
}
