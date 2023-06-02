<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateStrategy;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

class MessageHandler
{
    public function __construct(
        private readonly Flavour $flavour,
        private readonly LockAggregateStrategy $lockAggregateStrategy,
        private readonly LockAggregateStrategy $noLockAggregateStrategy,
    ) {
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

    public function handlesMessageBag(
        EventEngineMessage $message,
        LockAggregateStrategy|null $lockAggregateStrategy = null,
    ): mixed {
        $message = $this->flavour->convertMessageReceivedFromNetwork($message);

        $lockAggregateStrategy ??= $this->lockAggregateStrategy;

        return ($lockAggregateStrategy)($message);
    }
}
