<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

use ADS\Bundle\EventEngineBundle\Message\Message;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\QueueableExtractor;
use EventEngine\EventEngine;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Messaging\MessageBag;
use EventEngine\Messaging\MessageDispatcher;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

use function is_a;
use function reset;

final class MessengerMessageProducer implements MessageProducer, MessageDispatcher
{
    public const ASYNC_METADATA = ['async' => true];
    public const NO_ASYNC_METADATA = ['async' => false];

    public const LOCK = ['lock' => true];
    public const NO_LOCK = ['lock' => false];

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $commandLowPriorityBus,
        private readonly MessageBusInterface $eventBus,
        private readonly MessageBusInterface $queryBus,
        private readonly Flavour $flavour,
        private readonly EventEngine|null $eventEngine = null,
        private readonly QueueableExtractor $queueableExtractor,
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

        $messageData = [
            'payload' => $payload,
            'metadata' => $metadata,
        ];

        if (isset($metadata['messageUuid'])) {
            $messageData['uuid'] = $metadata['messageUuid'];
            unset($metadata['messageUuid']);
        }

        return $this->eventEngine
            ->messageFactory()
            ->createMessageFromArray(
                $messageClass,
                $messageData,
            );
    }

    public function produce(EventEngineMessage $messageToPutOnTheQueue): mixed
    {
        if ($this->sendAsync($messageToPutOnTheQueue)) {
            // We need to lock the aggregate if we send the message async.
            $messageToPutOnTheQueue = $messageToPutOnTheQueue->withAddedMetadata('lock', true);
            $messageToPutOnTheQueue = $this->flavour->prepareNetworkTransmission($messageToPutOnTheQueue);
        }

        try {
            $envelop = $this->dispatchMessage($messageToPutOnTheQueue);
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

    private function sendAsync(EventEngineMessage $messageToPutOnTheQueue): bool
    {
        /** @var bool|null $sendAsync */
        $sendAsync = $messageToPutOnTheQueue->getMetaOrDefault('async', null);

        if ($sendAsync !== null) {
            return $sendAsync;
        }

        /** @var Message|null $message */
        $message = $messageToPutOnTheQueue->getOrDefault(MessageBag::MESSAGE, null);

        if ($message instanceof Queueable) {
            return $message::__queue();
        }

        $messageClass = $messageToPutOnTheQueue->messageName();

        return is_a($messageClass, Queueable::class, true) && $messageClass::__queue();
    }

    private function dispatchMessage(EventEngineMessage $messageToPutOnTheQueue): Envelope
    {
        $messageReflectionClass = new ReflectionClass($messageToPutOnTheQueue);
        $onLowPriority = $this->queueableExtractor->lowPriorityFromReflectionClass($messageReflectionClass) ?? false;

        $bus = $this->queryBus;

        if ($messageToPutOnTheQueue->messageType() === EventEngineMessage::TYPE_COMMAND) {
            if ($onLowPriority) {
                $bus = $this->commandLowPriorityBus;
            } else {
                $bus = $this->commandBus;
            }
        } elseif ($messageToPutOnTheQueue->messageType() === EventEngineMessage::TYPE_EVENT) {
            $bus = $this->eventBus;
        }

        return $bus->dispatch($messageToPutOnTheQueue);
    }
}
