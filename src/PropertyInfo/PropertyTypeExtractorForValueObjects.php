<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use ADS\Bundle\EventEngineBundle\Type\ComplexTypeExtractor;
use ADS\ValueObjects\BoolValue;
use ADS\ValueObjects\FloatValue;
use ADS\ValueObjects\IntValue;
use ADS\ValueObjects\ListValue;
use ADS\ValueObjects\StringValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use ReflectionClass;
use ReflectionType;
use RuntimeException;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;

use function array_filter;
use function sprintf;

final class PropertyTypeExtractorForValueObjects implements PropertyTypeExtractorInterface
{
    /**
     * @param class-string $class
     * @param array<mixed> $context
     *
     * @return array<PropertyInfoType>|null
     */
    public function getTypes(string $class, string $property, array $context = []): array|null
    {
        $propertyValueObjectReflectionClasses = $this->propertyValueObjectReflectionClasses($class, $property);

        if (empty($propertyValueObjectReflectionClasses)) {
            return null;
        }

        $types = [];
        foreach ($propertyValueObjectReflectionClasses as $propertyValueObjectReflectionClass) {
            $propertyInfoTypes = self::valueObjectInfoType(
                $propertyValueObjectReflectionClass,
                PropertyReflection::propertyReflectionTypeFromClassAndProperty($class, $property),
            );

            $propertyInfoTypes = [$propertyInfoTypes];

            $types = [
                ...$types,
                ...$propertyInfoTypes,
            ];
        }

        return $types;
    }

    /**
     * @param class-string $class
     *
     * @return ReflectionClass<ValueObject>[]
     */
    public function propertyValueObjectReflectionClasses(string $class, string $property): array
    {
        $propertyTypeReflectionClasses = PropertyReflection::propertyTypeReflectionClassesFromClassAndProperty(
            $class,
            $property,
        );

        /** @var array<ReflectionClass<ValueObject>> $propertyValueObjectReflectionClasses */
        $propertyValueObjectReflectionClasses = array_filter(
            $propertyTypeReflectionClasses,
            static fn (ReflectionClass $propertyReflectionClass) => $propertyReflectionClass
                ->implementsInterface(ValueObject::class)
        );

        return $propertyValueObjectReflectionClasses;
    }

    /** @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass */
    private static function valueObjectInfoType(
        ReflectionClass $reflectionClass,
        ReflectionType|null $reflectionPropertyType = null,
    ): PropertyInfoType {
        $className = $reflectionClass->getName();
        $class = ComplexTypeExtractor::isClassComplexType($className)
            ? $className
            : null;

        return new PropertyInfoType(
            $class
                ? PropertyInfoType::BUILTIN_TYPE_OBJECT
                : self::builtInTypeFromReflectionClass($reflectionClass),
            $reflectionPropertyType?->allowsNull() ?? false,
            $class,
            $reflectionClass->implementsInterface(ListValue::class),
            self::collectionKey($reflectionClass),
            self::collectionValue($reflectionClass),
        );
    }

    /** @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass */
    private static function collectionValueInfoType(
        ReflectionClass $reflectionClass,
    ): PropertyInfoType {
        if ($reflectionClass->implementsInterface(ValueObject::class)) {
            return self::valueObjectInfoType($reflectionClass);
        }

        return new PropertyInfoType(
            self::builtInTypeFromReflectionClass($reflectionClass),
            false,
            $reflectionClass->getName(),
            false,
            null,
            null,
        );
    }

    /**
     * @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass
     *
     * @return array<PropertyInfoType>|null
     */
    private static function collectionKey(ReflectionClass $reflectionClass): array|null
    {
        if (! $reflectionClass->implementsInterface(ListValue::class)) {
            return null;
        }

        // todo don't hardcode this. But fetch it from the list. Metadata still needs to be added.
        return [
            new PropertyInfoType(PropertyInfoType::BUILTIN_TYPE_INT),
            new PropertyInfoType(PropertyInfoType::BUILTIN_TYPE_STRING),
        ];
    }

    /** @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass */
    private static function collectionValue(ReflectionClass $reflectionClass): PropertyInfoType|null
    {
        if (! $reflectionClass->implementsInterface(ListValue::class)) {
            return null;
        }

        /** @var class-string<ListValue<ValueObject|ImmutableRecord>> $class */
        $class = $reflectionClass->getName();
        /** @var class-string<ValueObject|ImmutableRecord> $itemClass */
        $itemClass = $class::itemType();

        return self::collectionValueInfoType(new ReflectionClass($itemClass));
    }

    /** @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass */
    private static function builtInTypeFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        return match (true) {
            $reflectionClass->implementsInterface(StringValue::class) => PropertyInfoType::BUILTIN_TYPE_STRING,
            $reflectionClass->implementsInterface(ListValue::class) => PropertyInfoType::BUILTIN_TYPE_ARRAY,
            $reflectionClass->implementsInterface(BoolValue::class) => PropertyInfoType::BUILTIN_TYPE_BOOL,
            $reflectionClass->implementsInterface(FloatValue::class) => PropertyInfoType::BUILTIN_TYPE_FLOAT,
            $reflectionClass->implementsInterface(IntValue::class) => PropertyInfoType::BUILTIN_TYPE_INT,
            $reflectionClass->implementsInterface(ImmutableRecord::class) => PropertyInfoType::BUILTIN_TYPE_OBJECT,
            default => throw new RuntimeException(
                sprintf(
                    "No symfony type mapping found for class '%s'.",
                    $reflectionClass->getName(),
                ),
            )
        };
    }
}
