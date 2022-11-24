<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateCommandStrategy;
use ADS\Bundle\EventEngineBundle\Messenger\Message\CommandMessageWrapper;
use EventEngine\Messaging\MessageBag;

class AsyncCommandHandler
{
    public function __construct(
        private LockAggregateCommandStrategy $lockAggregateCommand
    ) {
    }

    public function __invoke(CommandMessageWrapper $messageWrapper): mixed
    {
        /** @var MessageBag $messageBag */
        $messageBag = $messageWrapper->message();

        return ($this->lockAggregateCommand)($messageBag);
    }
}
