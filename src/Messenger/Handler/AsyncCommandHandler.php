<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateStrategy;
use ADS\Bundle\EventEngineBundle\Messenger\Message\CommandMessageWrapper;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[Deprecated]
#[AsMessageHandler(bus: 'command.bus', fromTransport: 'event_engine.command')]
class AsyncCommandHandler
{
    public function __construct(
        private Flavour $flavour,
        private LockAggregateStrategy $lockAggregateCommand,
    ) {
    }

    public function __invoke(CommandMessageWrapper $messageWrapper): mixed
    {
        /** @var MessageBag $messageBag */
        $messageBag = $messageWrapper->message();
        /** @var MessageBag $messageBag */
        $messageBag = $this->flavour->convertMessageReceivedFromNetwork($messageBag);

        return ($this->lockAggregateCommand)($messageBag);
    }
}
