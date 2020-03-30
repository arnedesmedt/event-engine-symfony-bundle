<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

interface AggregateCommand extends Command
{
    public function __aggregateId() : string;

    public function __aggregateMethod() : string;
}
