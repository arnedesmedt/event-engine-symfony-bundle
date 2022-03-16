<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;
use stdClass;
use Symfony\Component\Messenger\MessageBusInterface;

final class ExtendedEventEngine implements MessageProducer
{
    public function __construct(
        private Flavour $flavour,
        private MessageBusInterface $bus,
    ) {
    }

    public function produce(Message $message): mixed
    {
        $transferableMessage = $this->flavour->prepareNetworkTransmission($message);
        $this->bus->dispatch($transferableMessage);

        return new stdClass();
    }
}
