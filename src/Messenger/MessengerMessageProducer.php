<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

use ADS\Bundle\EventEngineBundle\Message\Message;
use EventEngine\EventEngine;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Messaging\MessageBag;
use EventEngine\Messaging\MessageDispatcher;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

use function array_diff_key;
use function reset;

final class MessengerMessageProducer implements MessageProducer, MessageDispatcher
{
    public const ASYNC_METADATA = ['async' => true];

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
        if ($messageOrName instanceof EventEngineMessage) {
            return $this->produce($messageOrName);
        }

        return $this->produce($this->messageBag($messageOrName, $payload, $metadata));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    private function messageBag(string $messageClass, array $payload, array $metadata): EventEngineMessage
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

    public function produce(EventEngineMessage $message): mixed
    {
        /** @var Message $messageToPutOnTheQueue */
        $messageToPutOnTheQueue = $message->get(MessageBag::MESSAGE);
        $metadata = $message->metadata();

        $sendAsync = $message->getMetaOrDefault('async', null)
            ?? $messageToPutOnTheQueue instanceof Queueable;
        $emptyMetadata = empty(array_diff_key($metadata, self::ASYNC_METADATA)) && ($metadata['async'] ?? false);

        if (! $emptyMetadata) {
            // We loose the metadata if it's not send as a message bag.
            $messageToPutOnTheQueue = $message;
        }

        if ($sendAsync && $messageToPutOnTheQueue instanceof EventEngineMessage) {
            $messageToPutOnTheQueue = $this->flavour->prepareNetworkTransmission($message);
        }

        try {
            /** @var Envelope $envelop */
            $envelop = match ($message->messageType()) {
                EventEngineMessage::TYPE_COMMAND => $this->commandBus->dispatch($messageToPutOnTheQueue),
                EventEngineMessage::TYPE_EVENT => $this->eventBus->dispatch($messageToPutOnTheQueue),
                default => $this->queryBus->dispatch($messageToPutOnTheQueue),
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
