<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

interface QueueableMessage
{
    public function __queueable(): bool;
}
