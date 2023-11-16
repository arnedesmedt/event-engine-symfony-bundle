<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Listener;

use ADS\Bundle\EventEngineBundle\Event\Listener;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestInterfaceEvent;

class TestInterfaceListener implements Listener
{
    public static function __handleEvents(): array|string
    {
        return [
            TestInterfaceEvent::class,
        ];
    }

    public function __invoke(TestInterfaceEvent $event): void
    {
        echo 'test';
    }
}
