<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Retry;

use ADS\Bundle\EventEngineBundle\Command\Command;
use ADS\Bundle\EventEngineBundle\Message\Message;

class CommandRetry extends Retry
{
    protected function messageAllowed(Message $message): bool
    {
        return $message instanceof Command;
    }
}
