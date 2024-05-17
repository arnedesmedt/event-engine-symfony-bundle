<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Queueable
{
    public function __construct(
        private readonly bool $queue = false,
        private readonly int $maxRetries = 5,
        private readonly int $delayInMilliseconds = 5000,
        private readonly int $multiplier = 2,
        private readonly int $maxDelayInMilliseconds = 5 * 60 * 1000,
        private readonly bool $sendToLinkedFailureTransport = true,
        private readonly bool $lowPriority = true,
    ) {
    }

    public function queue(): bool
    {
        return $this->queue;
    }

    public function maxRetries(): int
    {
        return $this->maxRetries;
    }

    public function delayInMilliseconds(): int
    {
        return $this->delayInMilliseconds;
    }

    public function multiplier(): int
    {
        return $this->multiplier;
    }

    public function maxDelayInMilliseconds(): int
    {
        return $this->maxDelayInMilliseconds;
    }

    public function sendToLinkedFailureTransport(): bool
    {
        return $this->sendToLinkedFailureTransport;
    }

    public function lowPriority(): bool
    {
        return $this->lowPriority;
    }
}
