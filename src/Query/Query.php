<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

interface Query
{
    /**
     * @return class-string|string
     */
    public static function __resolver(): string;
}
