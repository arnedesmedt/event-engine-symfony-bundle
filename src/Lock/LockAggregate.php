<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use EventEngine\EventEngine;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use Psr\Log\LoggerInterface;
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
    ) {
    }

    public function __invoke(Message $message): mixed
    {
        /** @var JsonSchemaAwareRecord $customMessage */
        $customMessage = $message->get(MessageBag::MESSAGE);
        $lock = null;
        $lockId = '';

        $aggregateId = $this->aggregateCommandExtractor->aggregateIdFromAggregateCommand($customMessage);

        if ($aggregateId) {
            $commandRouting = $this->eventEngine->compileCacheableConfig()['compiledCommandRouting'];
            $aggregateType = $commandRouting[$customMessage::class]['aggregateType'];

            $lockId = sprintf('aggregate:%s-id:%s', $aggregateType, $aggregateId);
            $lock = $this->aggregateLockFactory->createLock($lockId);

            $this->logger->info(sprintf("Trying to acquire lock for '%s'.", $lockId));
            $lock->acquire(true);
            $this->logger->info(sprintf("Lock acquired for '%s'.", $lockId));
        }

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
