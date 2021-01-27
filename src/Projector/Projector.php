<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use EventEngine\Projecting\CustomEventProjector;

interface Projector extends CustomEventProjector
{
    /** @return array<int, class-string> */
    public static function getEvents(): array;

    public static function getProjectionName(): string;

    public static function getVersion(): string;

    public static function generateOwnCollectionName(): string;

    public static function getStateClassName(): string;
}
