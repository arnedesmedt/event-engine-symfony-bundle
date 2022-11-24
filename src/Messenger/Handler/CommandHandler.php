<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateCommandStrategy;
use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;

class CommandHandler extends Handler
{
    public function __construct(
        EventEngine $eventEngine,
        private LockAggregateCommandStrategy $lockAggregateCommand
    ) {
        parent::__construct($eventEngine);
    }

    public function __invoke(MessageBag $messageBag): mixed
    {
        return ($this->lockAggregateCommand)($messageBag);
    }
}
