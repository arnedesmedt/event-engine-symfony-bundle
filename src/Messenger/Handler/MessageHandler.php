<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateStrategy;
use ADS\Bundle\EventEngineBundle\Message\Message;
use EventEngine\EventEngine;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function method_exists;

class MessageHandler
{
    public function __construct(
        private readonly Flavour $flavour,
        private readonly EventEngine $eventEngine,
        private readonly LockAggregateStrategy $lockAggregateStrategy,
        private readonly LockAggregateStrategy $noLockAggregateStrategy,
    ) {
    }

    #[AsMessageHandler(bus: 'command')]
    public function handlesCommandMessage(Message $message): mixed
    {
        return $this->handlesMessage($message);
    }

    #[AsMessageHandler(bus: 'event')]
    public function handlesEventMessage(Message $message): mixed
    {
        return $this->handlesMessage($message);
    }

    #[AsMessageHandler(bus: 'query')]
    public function handlesQueryMessage(Message $message): mixed
    {
        return $this->handlesMessage($message, $this->noLockAggregateStrategy);
    }

    #[AsMessageHandler(bus: 'command')]
    public function handlesCommandEventEngineMessage(EventEngineMessage $message): mixed
    {
        return $this->handlesMessageBag($message);
    }

    #[AsMessageHandler(bus: 'event')]
    public function handlesEventEventEngineMessage(EventEngineMessage $message): mixed
    {
        return $this->handlesMessageBag($message);
    }

    #[AsMessageHandler(bus: 'query')]
    public function handlesQueryEventEngineMessage(EventEngineMessage $message): mixed
    {
        return $this->handlesMessageBag($message, $this->noLockAggregateStrategy);
    }

    public function handlesMessage(
        Message $message,
        LockAggregateStrategy|null $lockAggregateStrategy = null,
    ): mixed {
        $messageClass = $message::class;

        if (method_exists($messageClass, 'buildPropTypeMapIfEmpty')) {
            $messageClass::buildPropTypeMapIfEmpty();
        }

        $eventEngineMessage = $this->eventEngine->messageFactory()->createMessageFromArray(
            $messageClass,
            ['payload' => $message->toArray()],
        );

        return $this->handlesMessageBag($eventEngineMessage, $lockAggregateStrategy);
    }

    public function handlesMessageBag(
        EventEngineMessage $message,
        LockAggregateStrategy|null $lockAggregateStrategy = null,
    ): mixed {
        $message = $this->flavour->convertMessageReceivedFromNetwork($message);

        $lockAggregateStrategy ??= $this->lockAggregateStrategy;

        return ($lockAggregateStrategy)($message);
    }
}
