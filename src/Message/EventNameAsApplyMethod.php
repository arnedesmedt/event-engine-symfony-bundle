<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use function array_pop;
use function explode;
use function sprintf;

trait EventNameAsApplyMethod
{
    public function __applyMethod() : string
    {
        $parts = explode('\\', static::class);

        return sprintf('when%s', array_pop($parts));
    }
}
