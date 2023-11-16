<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Projector;

use ADS\Bundle\EventEngineBundle\Projector\DefaultProjector;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestInterfaceEvent;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestState;

class TestInterfaceProjector extends DefaultProjector
{
    /** @inheritDoc */
    public static function events(): array
    {
        return [
            TestInterfaceEvent::class,
        ];
    }

    public static function stateClass(): string
    {
        return TestState::class;
    }

    public static function projectionName(): string
    {
        return 'ProjectorInterfaceName';
    }
}
