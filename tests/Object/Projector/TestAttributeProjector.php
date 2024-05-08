<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Projector;

use ADS\Bundle\EventEngineBundle\Attribute\Projector;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestAttributeEvent;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestState;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestStates;

#[Projector(
    name: 'ProjectorAttributeName',
    version: '1.0.0',
    stateClass: TestState::class,
    statesClass: TestStates::class,
    eventsToHandle: [
        TestAttributeEvent::class,
    ],
)]
class TestAttributeProjector
{
}
