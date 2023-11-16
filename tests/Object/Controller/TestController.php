<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Controller;

use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceControllerCommand;

class TestController
{
    public function __invoke(TestInterfaceControllerCommand|TestAttributeControllerCommand $command): mixed
    {
        return null;
    }
}
