<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;
use Symfony\Component\Lock\LockFactory;

use function sprintf;

final class LockAggregateCommand
{
    public function __construct(
        private EventEngine $eventEngine,
        private LockFactory $aggregateLockFactory,
    ) {
    }

    public function __invoke(MessageBag $messageBag): mixed
    {
        $message = $messageBag->get(MessageBag::MESSAGE);
        $lock = null;

        if ($message instanceof AggregateCommand) {
            $aggregateId = $message->__aggregateId();
            $commandRouting = $this->eventEngine->compileCacheableConfig()['compiledCommandRouting'];
            $aggregateType = $commandRouting[$message::class]['aggregateType'];

            $lock = $this->aggregateLockFactory->createLock(
                sprintf('aggregate:%s-id:%s', $aggregateType, $aggregateId)
            );
            $lock->acquire(true);
        }

        try {
            $result = $this->eventEngine->produce($messageBag);
        } finally {
            if ($lock !== null) {
                $lock->release();
            }
        }

        return $result;
    }
}
