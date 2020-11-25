<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Event;

use ReflectionClass;

use function sprintf;

trait DefaultEvent
{
    public function __applyMethod(): string
    {
        return sprintf('when%s', (new ReflectionClass(static::class))->getShortName());
    }
}
