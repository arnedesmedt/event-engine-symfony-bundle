<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

trait DefaultPreProcessCommand
{
    /**
     * An aggregate method needs to be defined for preprocessor, but it's not used.
     */
    public static function __aggregateMethod(): string
    {
        return 'preProcessorAggregateMethod';
    }
}
