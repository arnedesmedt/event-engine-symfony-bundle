<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Service;

use ADS\Bundle\EventEngineBundle\Message\Message;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;

class MessageFromEnvelope
{
    public function __construct(
        private readonly Flavour $flavour,
    ) {
    }

    public function __invoke(Envelope $envelope): Message
    {
        /** @var Message|EventEngineMessage $message */
        $message = $envelope->getMessage();

        if ($message instanceof EventEngineMessage) {
            $message = $this->flavour->convertMessageReceivedFromNetwork($message);
            $message = $message->get(MessageBag::MESSAGE);
        }

        if (! $message instanceof Message) {
            throw new RuntimeException(
                'Message is not a MessageWrapper, Message or EventEngine Message.',
            );
        }

        return $message;
    }
}
