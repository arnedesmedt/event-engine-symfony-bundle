<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Event;

use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use ReflectionClass;

use function sprintf;

trait DefaultEvent
{
    use JsonSchemaAwareRecordLogic;

    public function __applyMethod(): string
    {
        return sprintf('when%s', (new ReflectionClass(static::class))->getShortName());
    }
}
