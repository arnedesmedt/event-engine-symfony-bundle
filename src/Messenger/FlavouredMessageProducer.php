<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;

final class FlavouredMessageProducer implements MessageProducer
{
    public function __construct(
        private readonly Flavour $flavour,
        private readonly MessageProducer $messageProducer,
    ) {
    }

    public function produce(Message $message): mixed
    {
        $message = $this->flavour->prepareNetworkTransmission($message);

        return $this->messageProducer->produce($message);
    }
}
