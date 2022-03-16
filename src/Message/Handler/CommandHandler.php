<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message\Handler;

use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use EventEngine\EventEngine;
use EventEngine\Messaging\MessageBag;
use Symfony\Component\Lock\LockFactory;

use function sprintf;

class CommandHandler extends Handler
{
    public function __construct(
        EventEngine $eventEngine,
        private LockFactory $aggregateLockFactory,
    ) {
        parent::__construct($eventEngine);
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

        $result = parent::__invoke($messageBag);

        if ($lock !== null) {
            $lock->release();
        }

        return $result;
    }
}
