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
    use ClassOrAttributeExtractor;

    /**
     * @param ReflectionClass<AggregateRoot<JsonSchemaAwareRecord>> $reflectionClass
     *
     * @return class-string<JsonSchemaAwareRecord>
     */
    public function fromAggregateRootReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var class-string<AggregateRoot<JsonSchemaAwareRecord>> $aggregateRootClass */
        $aggregateRootClass = $this->needClassOrAttributeInstanceFromReflectionClass( // @phpstan-ignore-line
            $reflectionClass,
            AggregateRoot::class,
        );

        return $aggregateRootClass::stateClass();
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return class-string
     */
    public function fromProjectorReflectionClass(ReflectionClass $reflectionClass): string
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Projector::class,
            ProjectorAttribute::class,
        );

        return $classOrAttributeInstance instanceof ProjectorAttribute
            ? $classOrAttributeInstance->stateClass()
            : $classOrAttributeInstance::stateClass();
    }
}
