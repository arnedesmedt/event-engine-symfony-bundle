<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Middleware;

use ADS\Bundle\EventEngineBundle\Attribute\Queueable as QueueableAttribute;
use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Message\Message;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\CommandRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\EventRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\QueryRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Service\MessageFromEnvelope;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\QueueableExtractor;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Util\MetadataExtractor\AttributeExtractor;
use ADS\Util\MetadataExtractor\ClassExtractor;
use ADS\Util\MetadataExtractor\MetadataExtractor;
use EventEngine\Messaging\Message as EventEngineMessage;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\RecoverableExceptionInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableExceptionInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Throwable;

class DontSendToFailureTransportMiddleware implements MiddlewareInterface
{
    private readonly MetadataExtractor $metadataExtractor;

    public function __construct(
        private readonly CommandRetry $commandRetry,
        private readonly EventRetry $eventRetry,
        private readonly QueryRetry $queryRetry,
        private readonly MessageFromEnvelope $messageFromEnvelope,
        private readonly QueueableExtractor $queueableExtractor,
    ) {
        $this->metadataExtractor = new MetadataExtractor(
            new AttributeExtractor(),
            new ClassExtractor(),
        );
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (HandlerFailedException $e) {
            /** @var EventEngineMessage|Message $message */
            $message = $envelope->getMessage();

            // Send sync for normal message
            if ($message instanceof Message && ! ($message instanceof Queueable && $message::__queue())) {
                throw $e;
            }

            // Send sync for event engine message that contains the async metadata flag.
            if ($message instanceof EventEngineMessage && ! $message->getMetaOrDefault('async', false)) {
                throw $e;
            }

            if ($this->shouldRetry($e, $envelope)) {
                throw $e;
            }

            $message = ($this->messageFromEnvelope)($envelope);

            $reflectionClass = new ReflectionClass($message);
            $queueable = $this->metadataExtractor->attributeOrClassFromReflectionClass($reflectionClass, [
                Queueable::class,
                QueueableAttribute::class,
            ]);

            if ($queueable === null) {
                return $envelope;
            }

            $queueu = $this->queueableExtractor->queueFromReflectionClass($reflectionClass);
            $sendToLinkedFailureTransport = $this->queueableExtractor->sendToLinkedFailureTransportFromReflectionClass(
                $reflectionClass,
            );

            if ($queueu && $sendToLinkedFailureTransport) {
                throw $e;
            }

            return $envelope;
        }
    }

    /** @see SendFailedMessageForRetryListener::shouldRetry() */
    private function shouldRetry(Throwable $e, Envelope $envelope): bool
    {
        if ($e instanceof RecoverableExceptionInterface) {
            return true;
        }

        // if one or more nested Exceptions is an instance of RecoverableExceptionInterface we should retry
        // if ALL nested Exceptions are an instance of UnrecoverableExceptionInterface we should not retry
        if ($e instanceof HandlerFailedException) {
            $shouldNotRetry = true;
            foreach ($e->getWrappedExceptions() as $nestedException) {
                if ($nestedException instanceof RecoverableExceptionInterface) {
                    return true;
                }

                if (! $nestedException instanceof UnrecoverableExceptionInterface) {
                    $shouldNotRetry = false;
                    break;
                }
            }

            if ($shouldNotRetry) {
                return false;
            }
        }

        if ($e instanceof UnrecoverableExceptionInterface) {
            return false;
        }

        /** @var Message $message */
        $message = ($this->messageFromEnvelope)($envelope);

        $retryStrategy = match (true) {
            $message instanceof Query => $this->queryRetry,
            $message instanceof Event => $this->eventRetry,
            default => $this->commandRetry
        };

        return $retryStrategy->isRetryable($envelope, $e);
    }
}
