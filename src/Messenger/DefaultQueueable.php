<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

trait DefaultQueueable
{
    public static function __maxRetries(): int
    {
        return 5;
    }

    public static function __delayInMilliseconds(): int
    {
        return 5000;
    }

    public static function __multiplier(): int
    {
        return 2;
    }

    public static function __maxDelayInMilliseconds(): int
    {
        return 5 * 60 * 1000;
    }

    public static function __dispatchAsync(mixed $data): bool
    {
        return true;
    }
}
