<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

interface AggregateCommand extends Command
{
    public function __aggregateId(): string;

    public static function __aggregateMethod(): string;
}
