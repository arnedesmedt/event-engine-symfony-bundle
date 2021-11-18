<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use Ramsey\Uuid\UuidInterface;

interface MessageUuidAware
{
    public function setMessageUuid(UuidInterface $uuid): void;

    public function messageUuid(): UuidInterface;
}
