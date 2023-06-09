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
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

use function reset;

final class MessengerMessageProducer implements MessageProducer, MessageDispatcher
{
    public const ASYNC_METADATA = ['async' => true];

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $eventBus,
        private readonly MessageBusInterface $queryBus,
        private readonly Flavour $flavour,
        private readonly EventEngine|null $eventEngine = null,
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
        if ($this->eventEngine === null) {
            throw new RuntimeException('EventEngine is not set');
        }

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

    public function produce(EventEngineMessage $messageToPutOnTheQueue): mixed
    {
        /** @var Message $message */
        $message = $messageToPutOnTheQueue->get(MessageBag::MESSAGE);
        $sendAsync = $messageToPutOnTheQueue->getMetaOrDefault('async', null)
            ?? $message instanceof Queueable;

        if ($sendAsync) {
            $messageToPutOnTheQueue = $this->flavour->prepareNetworkTransmission($messageToPutOnTheQueue);
        }

        try {
            /** @var Envelope $envelop */
            $envelop = match ($messageToPutOnTheQueue->messageType()) {
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
