<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Event;

interface Listener
{
    /** @return array<class-string>|class-string */
    public static function __handleEvents(): array|string;
}
