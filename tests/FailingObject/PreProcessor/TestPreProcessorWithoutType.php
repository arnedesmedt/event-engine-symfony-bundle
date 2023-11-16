<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\FailingObject\PreProcessor;

use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor;

#[PreProcessor]
class TestPreProcessorWithoutType
{
    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint */
    public function __invoke($command): void // @phpstan-ignore-line
    {
        // TODO: Implement __invoke() method.
    }
}
