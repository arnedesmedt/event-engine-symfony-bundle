<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Retry;

use ADS\Bundle\EventEngineBundle\Message\Message;
use ADS\Bundle\EventEngineBundle\Query\Query;

class QueryRetry extends Retry
{
    protected function messageAllowed(Message $message): bool
    {
        return $message instanceof Query;
    }
}
