<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use EventEngine\Data\ImmutableRecord;
use ReflectionClass;

use function array_keys;
use function in_array;
use function method_exists;

final class PropertyRequiredExtractor
{
    /** @param class-string<ImmutableRecord> $class */
    public function fromClassAndProperty(string $class, string $property): bool|null
    {
        $reflectionClass = new ReflectionClass($class);

        if (! $reflectionClass->implementsInterface(ImmutableRecord::class)) {
            return null;
        }

        $propertyReflectionType = PropertyReflection::propertyReflectionTypeFromClassAndProperty($class, $property);

        $required = ! ($propertyReflectionType?->allowsNull() ?? false);

        if (method_exists($class, '__defaultProperties')) {
            $defaultProperties = $class::__defaultProperties();

            if (in_array($property, array_keys($defaultProperties))) {
                $required = false;
            }
        }

        return $required;
    }
}
