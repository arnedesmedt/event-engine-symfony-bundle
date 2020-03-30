<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

interface HasAggregateRoot
{
    /**
     * @return class-string|string
     */
    public static function __aggregateRoot();
}
