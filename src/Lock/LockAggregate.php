<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use EventEngine\EventEngine;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

use function sprintf;

final class LockAggregate implements LockAggregateStrategy
{
    public const NO_LOCK_METADATA = ['lock' => false];

    public function __construct(
        private EventEngine $eventEngine,
        private LockFactory $aggregateLockFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Message $message): mixed
    {
        $customMessage = $message->get(MessageBag::MESSAGE);
        $doLock = $message->getMetaOrDefault('lock', true);
        $lock = null;
        $lockId = '';

        if ($customMessage instanceof AggregateCommand && $doLock) {
            $aggregateId = $customMessage->__aggregateId();
            $commandRouting = $this->eventEngine->compileCacheableConfig()['compiledCommandRouting'];
            $aggregateType = $commandRouting[$customMessage::class]['aggregateType'];

            $lockId = sprintf('aggregate:%s-id:%s', $aggregateType, $aggregateId);
            $lock = $this->aggregateLockFactory->createLock($lockId);

            $this->logger->info(sprintf('Trying to acquire lock for \'%s\'.', $lockId));
            $lock->acquire(true);
            $this->logger->info(sprintf('Lock acquired for \'%s\'.', $lockId));
        }

        try {
            $result = $this->eventEngine->dispatch($message);
        } finally {
            if ($lock !== null) {
                $lock->release();
                $this->logger->info(sprintf('Lock released for \'%s\'.', $lockId));
            }
        }

        return $result;
    }
}
