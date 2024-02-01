<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

class PropertyTypeExtractor
{
    /**
     * @param class-string $class
     *
     * @return array<ReflectionClass<object>>
     */
    public static function propertyReflectionClassesFromClassAndProperty(string $class, string $property): array
    {
        $propertyReflectionType = self::propertyReflectionType($class, $property);

        return self::propertyReflectionClassesFromReflectionType($propertyReflectionType);
    }

    /** @param class-string $class */
    public static function propertyReflectionType(string $class, string $property): ReflectionType|null
    {
        $reflectionClass = new ReflectionClass($class);

        try {
            $reflectionProperty = $reflectionClass->getProperty($property);
        } catch (ReflectionException) {
            return null;
        }

        return $reflectionProperty->getType();
    }

    /** @return array<ReflectionClass<object>> */
    public static function propertyReflectionClassesFromReflectionType(ReflectionType|null $reflectionType): array
    {
        if ($reflectionType === null) {
            return [];
        }

        $namedReflectionTypes = $reflectionType instanceof ReflectionUnionType
        || $reflectionType instanceof ReflectionIntersectionType
            ? $reflectionType->getTypes()
            : [$reflectionType];

        $propertyTypeReflectionClasses = [];

        foreach ($namedReflectionTypes as $namedReflectionType) {
            if (! $namedReflectionType instanceof ReflectionNamedType || $namedReflectionType->isBuiltin()) {
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
