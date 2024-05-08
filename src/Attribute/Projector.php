<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Attribute(Attribute::TARGET_CLASS)]
class Projector
{
    /**
     * @param class-string<JsonSchemaAwareRecord> $stateClass
     * @param class-string<JsonSchemaAwareCollection> $statesClass
     * @param array<class-string<JsonSchemaAwareRecord>> $eventsToHandle
     */
    public function __construct(
        private readonly string $name,
        private readonly string $version,
        private readonly string $stateClass,
        private readonly string $statesClass,
        private readonly array $eventsToHandle = [],
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    /** @return class-string<JsonSchemaAwareRecord> */
    public function stateClass(): string
    {
        return $this->stateClass;
    }

    /** @return class-string<JsonSchemaAwareCollection> */
    public function statesClass(): string
    {
        return $this->statesClass;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function eventsToHandle(): array
    {
        return $this->eventsToHandle;
    }
}
