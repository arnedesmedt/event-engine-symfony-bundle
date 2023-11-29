<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ReflectionClass;

use function reset;

final class AttributeExtractor
{
    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     *
     * @template T of object
     */
    public function attributeInstanceFromReflectionClassAndAttribute(
        ReflectionClass $reflectionClass,
        string $attributeClass,
    ): object|null {
        $attributes = $reflectionClass->getAttributes($attributeClass);

        if (empty($attributes)) {
            return null;
        }

        $attribute = reset($attributes);

        return $attribute->newInstance();
    }

    /**
     * @param class-string<object>|object $class
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     *
     * @template T of object
     */
    public function attributeInstanceFromClassAndAttribute(
        mixed $class,
        string $attributeClass,
    ): object|null {
        return $this->attributeInstanceFromReflectionClassAndAttribute(
            new ReflectionClass($class),
            $attributeClass,
        );
    }

    /**
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     *
     * @template T of object
     */
    public function attributeInstanceFromInstanceAndAttribute(
        object $record,
        string $attributeClass,
    ): object|null {
        return $this->attributeInstanceFromReflectionClassAndAttribute(
            new ReflectionClass($record),
            $attributeClass,
        );
    }
}
