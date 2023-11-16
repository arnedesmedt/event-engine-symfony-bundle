<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use ADS\Bundle\EventEngineBundle\Event\Event;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Listener
{
    /** @param array<class-string<Event>> $eventsToHandle */
    public function __construct(
        private readonly array $eventsToHandle = [],
    ) {
    }

    /** @return array<class-string<Event>> */
    public function eventsToHandle(): array
    {
        return $this->eventsToHandle;
    }
}
