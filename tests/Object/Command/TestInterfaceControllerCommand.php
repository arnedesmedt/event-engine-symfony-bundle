<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Command;

use ADS\Bundle\EventEngineBundle\Command\ControllerCommand;
use ADS\Bundle\EventEngineBundle\Command\DefaultControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Controller\TestController;

class TestInterfaceControllerCommand implements ControllerCommand
{
    use DefaultControllerCommand;

    private string $test;

    public static function __controller(): string
    {
        return TestController::class;
    }
}
