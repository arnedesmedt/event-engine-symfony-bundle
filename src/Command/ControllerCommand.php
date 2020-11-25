<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

interface ControllerCommand extends Command
{
    /**
     * @return class-string
     */
    public static function __controller(): string;
}
