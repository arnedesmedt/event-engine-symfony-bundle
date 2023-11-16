<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand as AggregateCommandAttribute;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ReflectionClass;

class AggregateCommandExtractor
{
    use ClassOrAttributeExtractor;

    /** @param ReflectionClass<object> $reflectionClass */
    public function newFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        $classOrInstanceAttribute = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            AggregateCommand::class,
            AggregateCommandAttribute::class,
        );

        return $classOrInstanceAttribute instanceof AggregateCommandAttribute
            ? $classOrInstanceAttribute->newAggregate()
            : $classOrInstanceAttribute::__newAggregate();
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function aggregateMethodFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        $classOrInstanceAttribute = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            AggregateCommand::class,
            AggregateCommandAttribute::class,
        );

        return $classOrInstanceAttribute instanceof AggregateCommandAttribute
            ? $classOrInstanceAttribute->aggregateMethod()
            : $classOrInstanceAttribute::__aggregateMethod();
    }
}
