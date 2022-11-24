<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger;

interface Queueable
{
    public static function __maxRetries(): int;

    public static function __delayInMilliseconds(): int;

    public static function __multiplier(): int;

    public static function __maxDelayInMilliseconds(): int;

    public static function __dispatchAsync(): bool;

    public static function __sendToLinkedFailureTransport(): bool;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<class-string>
     */
    public static function __forkMessage(array $payload): ?array;
}
