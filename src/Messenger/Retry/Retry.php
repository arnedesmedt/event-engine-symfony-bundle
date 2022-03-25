<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Retry;

use ADS\Bundle\EventEngineBundle\Messenger\Message\MessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Throwable;

abstract class Retry implements RetryStrategyInterface
{
    public function __construct(private Flavour $flavour)
    {
    }

    public function isRetryable(Envelope $message, ?Throwable $throwable = null): bool
    {
        /** @var MessageWrapper $messageWrapper */
        $messageWrapper = $message->getMessage();
        $messageBag = $this->flavour->convertMessageReceivedFromNetwork($messageWrapper->message());

        if (! $messageBag instanceof MessageBag || ! $this->messageBagAllowed($messageBag)) {
            return false;
        }

        $eventEngineMessage = $messageBag->get(MessageBag::MESSAGE);

        if (! $eventEngineMessage instanceof Queueable) {
            return false;
        }

        $retries = RedeliveryStamp::getRetryCountFromEnvelope($message);

        return $retries < $eventEngineMessage::__maxRetries();
    }

    public function getWaitingTime(Envelope $message, ?Throwable $throwable = null): int
    {
        /** @var MessageWrapper $messageWrapper */
        $messageWrapper = $message->getMessage();
        $messageBag = $this->flavour->convertMessageReceivedFromNetwork($messageWrapper->message());
        /** @var Queueable $eventEngineMessage */
        $eventEngineMessage = $messageBag->get(MessageBag::MESSAGE);

        $retries = RedeliveryStamp::getRetryCountFromEnvelope($message);
        $delay = $eventEngineMessage::__delayInMilliseconds() * $eventEngineMessage::__multiplier() ** $retries;

        if (
            $delay > $eventEngineMessage::__maxDelayInMilliseconds()
            && $eventEngineMessage::__maxDelayInMilliseconds() !== 0
        ) {
            return $eventEngineMessage::__maxDelayInMilliseconds();
        }

        return $delay;
    }

    abstract protected function messageBagAllowed(MessageBag $messageBag): bool;
}
