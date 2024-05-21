<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

class PropertyReflection
{
    /** @param class-string $class */
    public static function propertyReflectionFromClassAndProperty(
        string $class,
        string $property,
    ): ReflectionProperty|null {
        $reflectionClass = new ReflectionClass($class);

        try {
            $reflectionProperty = $reflectionClass->getProperty($property);
        } catch (ReflectionException) {
            return null;
        }

        return $reflectionProperty;
    }

    /** @param class-string $class */
    public static function propertyReflectionTypeFromClassAndProperty(
        string $class,
        string $property,
    ): ReflectionType|null {
        $reflectionProperty = self::propertyReflectionFromClassAndProperty($class, $property);

        return $reflectionProperty?->getType();
    }

    /**
     * @param class-string $class
     *
     * @return array<ReflectionClass<object>>
     */
    public static function propertyTypeReflectionClassesFromClassAndProperty(string $class, string $property): array
    {
        $propertyReflectionType = self::propertyReflectionTypeFromClassAndProperty($class, $property);

        if (! $propertyReflectionType instanceof ReflectionType) {
            return [];
        }

        $namedReflectionTypes = $propertyReflectionType instanceof ReflectionUnionType
        || $propertyReflectionType instanceof ReflectionIntersectionType
            ? $propertyReflectionType->getTypes()
            : [$propertyReflectionType];

        $propertyTypeReflectionClasses = [];

        foreach ($namedReflectionTypes as $namedReflectionType) {
            if (! $namedReflectionType instanceof ReflectionNamedType) {
                continue;
            }

            if ($namedReflectionType->isBuiltin()) {
                continue;
            }

            /** @var class-string $possibleClass */
            $possibleClass = $namedReflectionType->getName();
            /** @var ReflectionClass<ValueObject|ImmutableRecord> $propertyTypeReflectionClass */
            $propertyTypeReflectionClass = new ReflectionClass($possibleClass);

            $propertyTypeReflectionClasses[] = $propertyTypeReflectionClass;
        }

        return $propertyTypeReflectionClasses;
    }
}
