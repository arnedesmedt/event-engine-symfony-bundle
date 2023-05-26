<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

use ADS\Bundle\EventEngineBundle\Messenger\Message\CommandMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Message\EventMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Message\QueryMessageWrapper;
use EventEngine\EventEngine;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

use function array_merge;
use function reset;

#[Deprecated]
final class QueueableEventEngine implements MessageProducer
{
    public function __construct(
        private Flavour $flavour,
        private MessageBusInterface $commandBus,
        private MessageBusInterface $eventBus,
        private MessageBusInterface $queryBus,
        private EventEngine $eventEngine,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public function dispatchAsync(string $messageClass, array $payload = [], array $metadata = []): mixed
    {
        $metadata = array_merge(
            $metadata,
            ['async' => true],
        );

        return $this->dispatch($messageClass, $payload, $metadata);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public function dispatch(string $messageClass, array $payload = [], array $metadata = []): mixed
    {
        return $this->produce($this->messageBag($messageClass, $payload, $metadata));
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

    public function produce(Message $messageBag): mixed
    {
        $transferableMessage = $this->flavour->prepareNetworkTransmission($messageBag);

        if ($transferableMessage->getMetaOrDefault('async', false)) {
            $transferableMessage = match ($messageBag->messageType()) {
                Message::TYPE_COMMAND => CommandMessageWrapper::fromMessage($transferableMessage),
                Message::TYPE_EVENT => EventMessageWrapper::fromMessage($transferableMessage),
                default => QueryMessageWrapper::fromMessage($transferableMessage),
            };
        }

        try {
            /** @var Envelope $envelop */
            $envelop = match ($messageBag->messageType()) {
                Message::TYPE_COMMAND => $this->commandBus->dispatch($transferableMessage),
                Message::TYPE_EVENT => $this->eventBus->dispatch($transferableMessage),
                default => $this->queryBus->dispatch($transferableMessage),
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
