<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Event
{
    public function __construct(
        private readonly string $applyMethod,
    ) {
    }

    public function applyMethod(): string
    {
        return $this->applyMethod;
    }
}
