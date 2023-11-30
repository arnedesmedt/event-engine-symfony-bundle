<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use Closure;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use RuntimeException;

final class MetadataExtractor
{
    private const METADATA_NOT_FOUND = '---no-metadata-found---';

    public function __construct(
        private readonly AttributeExtractor $attributeExtractor,
        private readonly ClassExtractor $classExtractor,
        private readonly InstanceExtractor $instanceExtractor,
    ) {
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param array<class-string> $attributesOrClasses
     */
    public function hasAttributeOrClassFromReflectionClass(
        ReflectionClass $reflectionClass,
        array $attributesOrClasses,
    ): bool {
        $attributeOrClass = $this->attributeOrClassFromReflectionClass(
            $reflectionClass,
            $attributesOrClasses,
        );

        return $attributeOrClass !== null;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param array<class-string> $attributesOrClasses
     */
    public function attributeOrClassFromReflectionClass(
        ReflectionClass $reflectionClass,
        array $attributesOrClasses,
    ): mixed {
        foreach ($attributesOrClasses as $attributeOrClass) {
            $attributeOrClass = $this->attributeExtractor->attributeInstanceFromReflectionClassAndAttribute(
                $reflectionClass,
                $attributeOrClass,
            ) ?? $this->classExtractor->classFromReflectionClassAndInterface(
                $reflectionClass,
                $attributeOrClass,
            );

            if ($attributeOrClass !== null) {
                return $attributeOrClass;
            }
        }

        return null;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param array<class-string> $attributesOrClasses
     */
    public function needAttributeOrClassFromReflectionClass(
        ReflectionClass $reflectionClass,
        array $attributesOrClasses,
    ): mixed {
        $attributeOrClass = $this->attributeOrClassFromReflectionClass(
            $reflectionClass,
            $attributesOrClasses,
        );

        if ($attributeOrClass === null) {
            throw new RuntimeException('No attribute or class found');
        }

        return $attributeOrClass;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param array<class-string, Closure> $attributesOrClasses
     */
    public function metadataFromReflectionClass(ReflectionClass $reflectionClass, array $attributesOrClasses): mixed
    {
        foreach ($attributesOrClasses as $attributeOrClass => $closure) {
            $attributeOrClass = $this->attributeExtractor->attributeInstanceFromReflectionClassAndAttribute(
                $reflectionClass,
                $attributeOrClass,
            ) ?? $this->classExtractor->classFromReflectionClassAndInterface(
                $reflectionClass,
                $attributeOrClass,
            );

            if ($attributeOrClass !== null) {
                return $closure($attributeOrClass);
            }
        }

        return self::METADATA_NOT_FOUND;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param array<class-string, Closure> $attributesOrClasses
     */
    public function needMetadataFromReflectionClass(ReflectionClass $reflectionClass, array $attributesOrClasses): mixed
    {
        $metadata = $this->metadataFromReflectionClass($reflectionClass, $attributesOrClasses);

        if ($metadata === self::METADATA_NOT_FOUND) {
            throw new RuntimeException('No metadata found');
        }

        return $metadata;
    }

    /** @param array<class-string> $attributesOrClasses */
    public function hasAttributeOrInstanceFromInstance(JsonSchemaAwareRecord $record, array $attributesOrClasses): bool
    {
        $attributeOrInstance = $this->attributeOrInstanceFromInstance($record, $attributesOrClasses);

        return $attributeOrInstance !== null;
    }

    /** @param array<class-string> $attributesOrClasses */
    public function attributeOrInstanceFromInstance(JsonSchemaAwareRecord $record, array $attributesOrClasses): mixed
    {
        foreach ($attributesOrClasses as $attributeOrClass) {
            $attributeOrInstance = $this->attributeExtractor->attributeInstanceFromInstanceAndAttribute(
                $record,
                $attributeOrClass,
            ) ?? $this->instanceExtractor->instanceFromInstanceAndInterface(
                $record,
                $attributeOrClass,
            );

            if ($attributeOrInstance !== null) {
                return $attributeOrInstance;
            }
        }

        return null;
    }

    /** @param array<class-string, Closure> $attributesOrClasses */
    public function metadataFromInstance(JsonSchemaAwareRecord $record, array $attributesOrClasses): mixed
    {
        foreach ($attributesOrClasses as $attributeOrClass => $closure) {
            $attributeOrInstance = $this->attributeExtractor->attributeInstanceFromInstanceAndAttribute(
                $record,
                $attributeOrClass,
            ) ?? $this->instanceExtractor->instanceFromInstanceAndInterface(
                $record,
                $attributeOrClass,
            );

            if ($attributeOrInstance !== null) {
                return $closure($attributeOrInstance, $record);
            }
        }

        return self::METADATA_NOT_FOUND;
    }

    /** @param array<class-string, Closure> $attributesOrClasses */
    public function needMetadataFromInstance(JsonSchemaAwareRecord $record, array $attributesOrClasses): mixed
    {
        $metadata = $this->metadataFromInstance($record, $attributesOrClasses);

        if ($metadata === self::METADATA_NOT_FOUND) {
            throw new RuntimeException('No metadata found');
        }

        return $metadata;
    }
}
