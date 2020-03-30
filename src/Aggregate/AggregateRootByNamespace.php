<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Util;

trait AggregateRootByNamespace
{
    /**
     * @inheritDoc
     */
    public static function aggregateRoot()
    {
        return Util::fromStateToAggregateClass(static::class);
    }
}
