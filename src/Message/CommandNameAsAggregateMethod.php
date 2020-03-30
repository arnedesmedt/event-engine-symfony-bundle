<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use ReflectionClass;

trait CommandNameAsAggregateMethod
{
    public function __aggregateMethod() : string
    {
        return (new ReflectionClass($this))->getShortName();
    }
}
