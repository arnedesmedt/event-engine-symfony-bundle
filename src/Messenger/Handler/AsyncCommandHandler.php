<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Lock\LockAggregateCommandStrategy;
use ADS\Bundle\EventEngineBundle\Messenger\Message\CommandMessageWrapper;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;

class AsyncCommandHandler
{
    public function __construct(
        private Flavour $flavour,
        private LockAggregateCommandStrategy $lockAggregateCommand,
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
