<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Retry;

use ADS\Bundle\EventEngineBundle\Message\Message;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use ADS\Bundle\EventEngineBundle\Messenger\Service\MessageFromEnvelope;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\QueueableExtractor;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Throwable;

abstract class Retry implements RetryStrategyInterface
{
    public function __construct(
        private readonly MessageFromEnvelope $messageFromEnvelope,
        private readonly QueueableExtractor $queueableExtractor,
    ) {
    }

    public function isRetryable(Envelope $message, Throwable|null $throwable = null): bool
    {
        $asyncMessage = ($this->messageFromEnvelope)($message);

        if (! $this->messageAllowed($asyncMessage)) {
            return false;
        }

        if (! $asyncMessage instanceof Queueable) {
            return false;
        }

        $retries = RedeliveryStamp::getRetryCountFromEnvelope($message);

        return $retries < $this->queueableExtractor->maxRetriesFromReflectionClass(new ReflectionClass($asyncMessage));
    }

    public function getWaitingTime(Envelope $message, Throwable|null $throwable = null): int
    {
        /** @var Message&Queueable $asyncMessage */
        $asyncMessage = ($this->messageFromEnvelope)($message);

        $reflectionClass = new ReflectionClass($asyncMessage);
        $delayInMilliseconds = $this->queueableExtractor->delayInMillisecondsFromReflectionClass($reflectionClass);
        $maxDelayInMilliseconds = $this->queueableExtractor->maxDelayInMillisecondsFromReflectionClass(
            $reflectionClass,
        );
        $multiplier = $this->queueableExtractor->multiplierFromReflectionClass($reflectionClass);
        $retries = RedeliveryStamp::getRetryCountFromEnvelope($message);
        $delay = $delayInMilliseconds * $multiplier ** $retries;

        if (
            $delay > $maxDelayInMilliseconds
            && $maxDelayInMilliseconds !== 0
        ) {
            return $maxDelayInMilliseconds;
        }

        return $delay;
    }

    abstract protected function messageAllowed(Message $message): bool;
}
