<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use EventEngine\Projecting\CustomEventProjector;

interface Projector extends CustomEventProjector
{
    /** @return array<int, class-string> */
    public static function events(): array;

    public static function projectionName(): string;

    public static function version(): string;

    public static function generateOwnCollectionName(): string;

    public static function stateClassName(): string;
}
