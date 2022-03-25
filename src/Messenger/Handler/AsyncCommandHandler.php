<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateCommand;
use ADS\Bundle\EventEngineBundle\Messenger\Message\CommandMessageWrapper;
use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;

class AsyncCommandHandler extends Handler
{
    public function __construct(
        EventEngine $eventEngine,
        Flavour $flavour,
        private LockAggregateCommand $lockAggregateCommand
    ) {
        parent::__construct($eventEngine, $flavour);
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
