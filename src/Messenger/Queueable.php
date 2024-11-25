<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

interface Queueable
{
    public static function __queue(): bool;

    public static function __maxRetries(): int;

    public static function __delayInMilliseconds(): int;

    public static function __multiplier(): int;

    public static function __maxDelayInMilliseconds(): int;

    public static function __sendToLinkedFailureTransport(): bool;

    public static function __lowPriority(): bool;
}
