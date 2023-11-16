<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ReflectionClass;
use RuntimeException;

use function implode;
use function is_array;
use function reset;
use function sprintf;

trait ClassOrAttributeExtractor
{
    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param class-string<Tinterface>|array<class-string<Tinterface>>|null $interfaces
     * @param class-string<Tattribute>|array<class-string<Tattribute>>|null $attributes
     *
     * @return class-string<Tinterface>|Tattribute|null
     *
     * @template Tinterface
     * @template Tattribute
     */
    private function classOrAttributeInstanceFromReflectionClass(
        ReflectionClass $reflectionClass,
        string|array|null $interfaces,
        string|array|null $attributes,
    ): mixed {
        if ($interfaces === null) {
            $interfaces = [];
        }

        if (! is_array($interfaces)) {
            $interfaces = [$interfaces];
        }

        foreach ($interfaces as $interface) {
            if ($reflectionClass->implementsInterface($interface)) {
                /** @var class-string<Tinterface> $class */
                $class = $reflectionClass->getName();

                return $class;
            }
        }

        if ($attributes === null) {
            $attributes = [];
        }

        if (! is_array($attributes)) {
            $attributes = [$attributes];
        }

        foreach ($attributes as $attribute) {
            $reflectionAttributes = $reflectionClass->getAttributes($attribute);

            if (! empty($reflectionAttributes)) {
                $reflectionAttribute = reset($reflectionAttributes);

                return $reflectionAttribute->newInstance();
            }
        }

        return null;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param class-string<Tinterface>|array<class-string<Tinterface>>|null $interfaces
     * @param class-string<Tattribute>|array<class-string<Tattribute>>|null $attributes
     *
     * @return class-string<Tinterface>|Tattribute
     *
     * @template Tinterface
     * @template Tattribute
     */
    private function needClassOrAttributeInstanceFromReflectionClass(
        ReflectionClass $reflectionClass,
        string|array|null $interfaces = null,
        string|array|null $attributes = null,
    ): mixed {
        $classOrAttributeInstance = $this->classOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            $interfaces,
            $attributes,
        );

        if ($classOrAttributeInstance !== null) {
            return $classOrAttributeInstance;
        }

        if ($interfaces === null) {
            $interfaces = [];
        }

        if (is_array($interfaces)) {
            $interfaces = implode('|', $interfaces);
        }

        if ($attributes === null) {
            $attributes = [];
        }

        if (is_array($attributes)) {
            $attributes = implode('|', $attributes);
        }

        if (empty($interfaces) && empty($attributes)) {
            throw new RuntimeException(
                'Can\'t find a class name or attribute instance if no interface or attribute is given.',
            );
        }

        $sections = [];

        if (! empty($interfaces)) {
            $sections[] = sprintf('implementation of \'%s\' found', $interfaces);
        }

        if (! empty($attributes)) {
            $sections[] = sprintf('attribute \'%s\' added', $attributes);
        }

        throw new RuntimeException(
            sprintf(
                'No %s for \'%s\'.',
                implode(' or ', $sections),
                $reflectionClass->getName(),
            ),
        );
    }
}
