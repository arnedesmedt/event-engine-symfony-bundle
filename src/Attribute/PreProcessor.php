<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PreProcessor
{
    public function __construct(
        private readonly int $priority = 0,
    ) {
    }

    public function priority(): int
    {
        return $this->priority;
    }
}
