<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\FailingObject\PreProcessor;

use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor;

#[PreProcessor]
class TestPreProcessorWithoutCommands
{
    public function __invoke(): void
    {
        // TODO: Implement __invoke() method.
    }
}
