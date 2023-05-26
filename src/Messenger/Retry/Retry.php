<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Retry;

use ADS\Bundle\EventEngineBundle\Message\Message;
use ADS\Bundle\EventEngineBundle\Messenger\Message\MessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use EventEngine\Messaging\Message as EventEngineMessage;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Throwable;

abstract class Retry implements RetryStrategyInterface
{
    public function __construct(private Flavour $flavour)
    {
    }

    public function isRetryable(Envelope $message, Throwable|null $throwable = null): bool
    {
        $asyncMessage = $this->asyncMessage($message);

        if (! $this->messageAllowed($asyncMessage)) {
            return false;
        }

        if (! $asyncMessage instanceof Queueable) {
            return false;
        }

        $retries = RedeliveryStamp::getRetryCountFromEnvelope($message);

        return $retries < $asyncMessage::__maxRetries();
    }

    public function getWaitingTime(Envelope $message, Throwable|null $throwable = null): int
    {
        /** @var Message&Queueable $asyncMessage */
        $asyncMessage = $this->asyncMessage($message);

        $retries = RedeliveryStamp::getRetryCountFromEnvelope($message);
        $delay = $asyncMessage::__delayInMilliseconds() * $asyncMessage::__multiplier() ** $retries;

        if (
            $delay > $asyncMessage::__maxDelayInMilliseconds()
            && $asyncMessage::__maxDelayInMilliseconds() !== 0
        ) {
            return $asyncMessage::__maxDelayInMilliseconds();
        }

        return $delay;
    }

    abstract protected function messageAllowed(Message $message): bool;

    private function asyncMessage(Envelope $envelope): Message
    {
        /** @var MessageWrapper|Message|EventEngineMessage $message */
        $message = $envelope->getMessage();

        if ($message instanceof MessageWrapper) {
            $message = $message->message();
        }

        if ($message instanceof EventEngineMessage) {
            $this->flavour->convertMessageReceivedFromNetwork($message);
            $message = $message->get(MessageBag::MESSAGE);
        }

        if (! $message instanceof Message) {
            throw new RuntimeException(
                'Message is not a MessageWrapper, Message or EventEngine Message.',
            );
        }

        return $message;
    }
}
