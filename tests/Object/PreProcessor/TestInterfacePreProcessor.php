<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor;

use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;

class TestInterfacePreProcessor implements PreProcessor
{
    public function __invoke(TestInterfaceAggregateCommand $command): TestInterfaceAggregateCommand
    {
        return $command;
    }
}
