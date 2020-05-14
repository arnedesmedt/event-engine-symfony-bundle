<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

interface Query extends HasResponses
{
    /**
     * @return class-string|string
     */
    public static function __resolver();
}
