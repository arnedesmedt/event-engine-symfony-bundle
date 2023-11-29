<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Attribute\Projector as ProjectorAttribute;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;

class StateClassExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /**
     * @param ReflectionClass<AggregateRoot<JsonSchemaAwareRecord>> $reflectionClass
     *
     * @return class-string<JsonSchemaAwareRecord>
     */
    public function fromAggregateRootReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var class-string<JsonSchemaAwareRecord> $stateClass */
        $stateClass = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<AggregateRoot> $class */
                AggregateRoot::class => static fn (string $class) => $class::stateClass(),
            ],
        );

        return $stateClass;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return class-string<JsonSchemaAwareRecord>
     */
    public function fromProjectorReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var class-string<JsonSchemaAwareRecord> $stateClass */
        $stateClass = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Projector> $class */
                Projector::class => static fn (string $class) => $class::stateClass(),
                ProjectorAttribute::class => static fn (ProjectorAttribute $attribute) => $attribute->stateClass(),
            ],
        );

        return $stateClass;
    }
}
