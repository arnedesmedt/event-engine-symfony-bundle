<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use Ramsey\Uuid\UuidInterface;

interface EventEngineMessageUuidAware
{
    public function setEventEngineMessageUuid(UuidInterface $uuid): void;

    public function eventEngineMessageUuid(): UuidInterface;
}
