<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Middleware;

use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class PickTransportMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Flavour $flavour)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        /** @var EventEngineMessage $eventEngineMessage */
        $eventEngineMessage = $envelope->getMessage();
        $message = $this->flavour->convertMessageReceivedFromNetwork($eventEngineMessage)->get(MessageBag::MESSAGE);
        $sendAsync = $eventEngineMessage->getMetaOrDefault('async', null)
            ?? ($message instanceof Queueable && $message::__queue());

        if ($sendAsync) {
            $envelope = $envelope->with(new TransportNamesStamp([$eventEngineMessage->messageType()]));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
