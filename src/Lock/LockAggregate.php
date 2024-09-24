<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\QueueableExtractor;
use EventEngine\EventEngine;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\GenericEvent;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use LogicException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

use function sprintf;

final class LockAggregate implements LockAggregateStrategy
{
    public function __construct(
        private readonly EventEngine $eventEngine,
        private readonly LockFactory $aggregateLockFactory,
        private readonly LoggerInterface $logger,
        private readonly AggregateCommandExtractor $aggregateCommandExtractor,
        private readonly QueueableExtractor $queueableExtractor,
    ) {
    }

    public function __invoke(Message $message): mixed
    {
        /** @var JsonSchemaAwareRecord $customMessage */
        $customMessage = $message->get(MessageBag::MESSAGE);

        /** @var string $aggregateId */
        $aggregateId = match ($message->messageType()) {
            Message::TYPE_COMMAND => $this->aggregateCommandExtractor
                ->aggregateIdFromAggregateCommand($customMessage),
            Message::TYPE_EVENT => $message->getMeta(GenericEvent::META_AGGREGATE_ID),
            default => throw new LogicException('No lock needed for query messages.'),
        };

        if (! $aggregateId) {
            return $this->eventEngine->dispatch($message);
        }

        if ($message->messageType() === Message::TYPE_EVENT) {
            $reflectionClass = new ReflectionClass($customMessage);
            $eventWasQueued = $message->getMetaOrDefault('async', null) ?? (
                $this->queueableExtractor->isQueueableFromReflectionClass($reflectionClass)
                && $this->queueableExtractor->queueFromReflectionClass($reflectionClass)
            );

            if (! $eventWasQueued) {
                return $this->eventEngine->dispatch($message);
            }
        }

        $aggregateType = match ($message->messageType()) {
            Message::TYPE_COMMAND => $this->eventEngine
                ->compileCacheableConfig()['compiledCommandRouting'][$customMessage::class]['aggregateType'],
            Message::TYPE_EVENT => $message->getMeta(GenericEvent::META_AGGREGATE_TYPE),
            default => throw new LogicException('No lock needed for query messages.'),
        };

        $lockId = sprintf('aggregate:%s-id:%s', $aggregateType, $aggregateId);
        $lock = $this->aggregateLockFactory->createLock($lockId);

        $this->logger->info(sprintf("Trying to acquire lock for '%s'.", $lockId));
        $lock->acquire(true);
        $this->logger->info(sprintf("Lock acquired for '%s'.", $lockId));

        try {
            $result = $this->eventEngine->dispatch($message);
        } finally {
            if ($lock instanceof SharedLockInterface) {
                $lock->release();
                $this->logger->info(sprintf("Lock released for '%s'.", $lockId));
            }
        }

        return $result;
    }
}
