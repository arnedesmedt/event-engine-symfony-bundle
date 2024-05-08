<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Attribute(Attribute::TARGET_CLASS)]
class Listener
{
    /** @param array<class-string<JsonSchemaAwareRecord>> $eventsToHandle */
    public function __construct(
        private readonly array $eventsToHandle = [],
    ) {
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function eventsToHandle(): array
    {
        return $this->eventsToHandle;
    }
}
