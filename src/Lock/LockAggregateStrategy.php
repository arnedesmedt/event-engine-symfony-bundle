<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use EventEngine\Messaging\Message;

interface LockAggregateStrategy
{
    public function __invoke(Message $message): mixed;
}
