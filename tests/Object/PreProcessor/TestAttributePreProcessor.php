<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor;

use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;

#[PreProcessor(priority: 5)]
class TestAttributePreProcessor
{
    public function __invoke(TestAttributeControllerCommand $command): TestAttributeControllerCommand
    {
        return $command;
    }
}
