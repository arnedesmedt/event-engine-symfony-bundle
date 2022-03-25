<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateCommand;
use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;

class CommandHandler extends Handler
{
    public function __construct(
        EventEngine $eventEngine,
        Flavour $flavour,
        private LockAggregateCommand $lockAggregateCommand
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
