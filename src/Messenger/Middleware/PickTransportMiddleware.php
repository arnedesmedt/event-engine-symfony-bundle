<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Middleware;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\QueueableExtractor;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class PickTransportMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Flavour $flavour,
        private readonly QueueableExtractor $queueableExtractor,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        /** @var EventEngineMessage $eventEngineMessage */
        $eventEngineMessage = $envelope->getMessage();
        /** @var object $message */
        $message = $this->flavour->convertMessageReceivedFromNetwork($eventEngineMessage)->get(MessageBag::MESSAGE);
        $reflectionMessage = new ReflectionClass($message);
        $sendAsync = $eventEngineMessage->getMetaOrDefault('async', null)
            ?? (
                $this->queueableExtractor->isQueueableFromReflectionClass($reflectionMessage)
                && $this->queueableExtractor->queueFromReflectionClass($reflectionMessage)
            );

        if ($sendAsync) {
            $envelope = $envelope->with(new TransportNamesStamp([$eventEngineMessage->messageType()]));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
