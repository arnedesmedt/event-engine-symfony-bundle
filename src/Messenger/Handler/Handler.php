<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Handler;

use ADS\Bundle\EventEngineBundle\Messenger\Message\MessageWrapper;
use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use JetBrains\PhpStorm\Deprecated;

#[Deprecated]
abstract class Handler
{
    public function __construct(
        protected EventEngine $eventEngine,
        protected Flavour $flavour,
    ) {
    }

    protected function dispatchMessageWrapper(MessageWrapper $messageWrapper): mixed
    {
        /** @var MessageBag $messageBag */
        $messageBag = $messageWrapper->message();

        return $this->dispatchMessageBag($messageBag);
    }

    protected function dispatchMessageBag(MessageBag $messageBag): mixed
    {
        return $this->eventEngine->dispatch($messageBag);
    }
}
