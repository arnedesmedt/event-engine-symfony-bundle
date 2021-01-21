<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use EventEngine\Projecting\CustomEventProjector;

interface Projector extends CustomEventProjector
{
    /** @return array<int, class-string> */
    public static function getEvents(): array;

    public static function getProjectionName(): string;
}
