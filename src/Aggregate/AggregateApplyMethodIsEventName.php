<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use function array_pop;
use function explode;
use function sprintf;

trait AggregateApplyMethodIsEventName
{
    public function applyMethod() : string
    {
        $parts = explode('\\', static::class);

        return sprintf('when%s', array_pop($parts));
    }
}
