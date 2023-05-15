<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use ADS\Bundle\EventEngineBundle\Command\Command;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PreProcessor
{
    /** @param array<class-string<Command>> $commandClasses */
    public function __construct(
        private readonly array $commandClasses,
        private int $priority = 0,
    ) {
    }

    /** @return array<class-string<Command>> $commandClasses */
    public function commandClasses(): array
    {
        return $this->commandClasses;
    }

    public function priority(): int
    {
        return $this->priority;
    }
}
