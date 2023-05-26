<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateStrategy;
use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CommandHandler extends Handler
{
    public function __construct(
        EventEngine $eventEngine,
        Flavour $flavour,
        private LockAggregateStrategy $lockAggregateCommand,
    ) {
        parent::__construct($eventEngine, $flavour);
    }

    public function __invoke(MessageBag $messageBag): mixed
    {
        /** @var MessageBag $messageBag */
        $messageBag = $this->flavour->convertMessageReceivedFromNetwork($messageBag);

        return ($this->lockAggregateCommand)($messageBag);
    }
}
