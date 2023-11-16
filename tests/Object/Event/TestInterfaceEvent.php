<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Event;

use ADS\Bundle\EventEngineBundle\Event\DefaultEvent;
use ADS\Bundle\EventEngineBundle\Event\Event;

class TestInterfaceEvent implements Event
{
    use DefaultEvent;

    private string $test;
}
