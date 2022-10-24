<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use EventEngine\Messaging\MessageBag;

interface LockAggregateCommandStrategy
{
    public function __invoke(MessageBag $messageBag): mixed;
}
