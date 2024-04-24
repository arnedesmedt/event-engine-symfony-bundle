<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\ContextProvider;

use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;

class TestContextProvider
{
    public function __invoke(TestAttributeAggregateCommand $command): TestAttributeAggregateCommand
    {
        return $command;
    }
}
