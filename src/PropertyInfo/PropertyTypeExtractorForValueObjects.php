<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

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
use Symfony\Component\PropertyInfo\Type;

use function array_filter;
use function array_map;
use function sprintf;

final class PropertyTypeExtractorForValueObjects implements PropertyTypeExtractorInterface
{
    /**
     * @param class-string $class
     * @param array<mixed> $context
     *
     * @return array<Type>|null
     */
    public function getTypes(string $class, string $property, array $context = []): array|null
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

        if (empty($propertyValueObjectReflectionClasses)) {
            return null;
        }

        return array_map(
            static fn (ReflectionClass $propertyValueObjectReflectionClass): Type => self::symfonyType(
                $propertyValueObjectReflectionClass,
                PropertyReflection::propertyReflectionTypeFromClassAndProperty($class, $property),
            ),
            $propertyValueObjectReflectionClasses,
        );
    }

    /** @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass */
    private static function symfonyType(
        ReflectionClass $reflectionClass,
        ReflectionType|null $reflectionPropertyType = null,
    ): Type {
        return new Type(
            self::builtInTypeFromReflectionClass($reflectionClass),
            $reflectionPropertyType?->allowsNull() ?? false,
            $reflectionClass->getName(),
            $reflectionClass->implementsInterface(ListValue::class),
            self::collectionKey($reflectionClass),
            self::collectionValue($reflectionClass),
        );
    }

    /**
     * @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass
     *
     * @return array<Type>|null
     */
    private static function collectionKey(ReflectionClass $reflectionClass): array|null
    {
        if (! $reflectionClass->implementsInterface(ListValue::class)) {
            return null;
        }

        // todo don't hardcode this. But fetch it from the list. Metadata still needs to be added.
        return [new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING)];
    }

    /** @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass */
    private static function collectionValue(ReflectionClass $reflectionClass): Type|null
    {
        if (! $reflectionClass->implementsInterface(ListValue::class)) {
            return null;
        }

        /** @var class-string<ListValue<ValueObject|ImmutableRecord>> $class */
        $class = $reflectionClass->getName();
        /** @var class-string<ValueObject|ImmutableRecord> $itemClass */
        $itemClass = $class::itemType();

        return self::symfonyType(new ReflectionClass($itemClass));
    }

    /** @param ReflectionClass<ValueObject|ImmutableRecord> $reflectionClass */
    private static function builtInTypeFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        return match (true) {
            $reflectionClass->implementsInterface(StringValue::class) => Type::BUILTIN_TYPE_STRING,
            $reflectionClass->implementsInterface(ListValue::class) => Type::BUILTIN_TYPE_ARRAY,
            $reflectionClass->implementsInterface(BoolValue::class) => Type::BUILTIN_TYPE_BOOL,
            $reflectionClass->implementsInterface(FloatValue::class) => Type::BUILTIN_TYPE_FLOAT,
            $reflectionClass->implementsInterface(IntValue::class) => Type::BUILTIN_TYPE_INT,
            $reflectionClass->implementsInterface(ImmutableRecord::class) => Type::BUILTIN_TYPE_OBJECT,
            default => throw new RuntimeException(
                sprintf(
                    "No symfony type mapping found for class '%s'.",
                    $reflectionClass->getName(),
                ),
            )
        };
    }
}
