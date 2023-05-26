<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

use EventEngine\EventEngine;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Messaging\MessageDispatcher;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

use function reset;

final class QueueableMessageProducer implements MessageProducer, MessageDispatcher
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $eventBus,
        private readonly MessageBusInterface $queryBus,
        private readonly Flavour $flavour,
        private readonly EventEngine $eventEngine,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     *
     * @throws Throwable
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    public function dispatch($messageOrName, array $payload = [], array $metadata = []): mixed
    {
        if ($messageOrName instanceof Message) {
            return $this->produce($messageOrName);
        }

        return $this->produce($this->messageBag($messageOrName, $payload, $metadata));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    private function messageBag(string $messageClass, array $payload, array $metadata): Message
    {
        return $this->eventEngine
            ->messageFactory()
            ->createMessageFromArray(
                $messageClass,
                [
                    'payload' => $payload,
                    'metadata' => $metadata,
                ],
            );
    }

    public function produce(Message $message): mixed
    {
        /** @var Message $innerMessage */
        $innerMessage = $message->get(MessageBag::MESSAGE);

        if (! $innerMessage instanceof Queueable && $message->getMetaOrDefault('async', false)) {
            // Put the message bag on the queue instead of the message itself.
            // Since the message is not queueable, the transport won't be changed by PickTransportMiddleware.
            // Therefore we need to send the complete messageBag
            // so it contains the metadata with the async property set to true, and now the transport will be changed.
            $innerMessage = $this->flavour->prepareNetworkTransmission($message);
        }

        try {
            /** @var Envelope $envelop */
            $envelop = match ($message->messageType()) {
                Message::TYPE_COMMAND => $this->commandBus->dispatch($innerMessage),
                Message::TYPE_EVENT => $this->eventBus->dispatch($innerMessage),
                default => $this->queryBus->dispatch($innerMessage),
            };
        } catch (HandlerFailedException $exception) {
            while ($exception instanceof HandlerFailedException) {
                /** @var Throwable $exception */
                $exception = $exception->getPrevious();
            }

            throw $exception;
        }

        $handledStamps = $envelop->all(HandledStamp::class);

        if (empty($handledStamps)) {
            return $envelop;
        }

        /** @var HandledStamp $handledStamp */
        $handledStamp = reset($handledStamps);

        return $handledStamp->getResult();
    }
}
