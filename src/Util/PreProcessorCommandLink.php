<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Util;

use ADS\Bundle\EventEngineBundle\Command\Command;
use EventEngine\Data\ImmutableRecord;
use EventEngine\Data\ImmutableRecordLogic;

class PreProcessorCommandLink implements ImmutableRecord
{
    use ImmutableRecordLogic;

    /** @var class-string<Command> */
    private string $commandClass;
    /** @var class-string */
    private string $preProcessorClass;
    private int $priority = 0;

    /** @return class-string<Command> */
    public function commandClass(): string
    {
        return $this->commandClass;
    }

    /** @return class-string<object> */
    public function preProcessorClass(): string
    {
        return $this->preProcessorClass;
    }

    public function priority(): int
    {
        return $this->priority;
    }
}
