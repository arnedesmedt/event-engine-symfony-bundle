<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

interface AggregateCommand extends Command
{
    public function aggregateId() : string;

    public function aggregateMethod() : string;
}
