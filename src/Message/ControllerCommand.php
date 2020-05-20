<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

interface ControllerCommand extends Command
{
    /**
     * @return class-string
     */
    public static function __controller() : string;
}
