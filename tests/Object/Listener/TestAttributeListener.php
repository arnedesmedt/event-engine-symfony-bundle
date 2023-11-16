<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Listener;

use ADS\Bundle\EventEngineBundle\Attribute\Listener;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestAttributeEvent;

#[Listener(
    eventsToHandle: [ // @phpstan-ignore-line
        TestAttributeEvent::class,
    ],
)]
class TestAttributeListener
{
    public function __invoke(TestAttributeEvent $event): void
    {
        echo 'test';
    }
}
