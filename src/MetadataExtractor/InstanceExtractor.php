<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use ReflectionException;

final class InstanceExtractor
{
    /** @param class-string $interface */
    public function instanceFromInstanceAndInterface(
        JsonSchemaAwareRecord $instance,
        string $interface,
    ): JsonSchemaAwareRecord|null {
        $reflectionClass = new ReflectionClass($instance);

        try {
            $implementsInterface = $reflectionClass->implementsInterface($interface);
        } catch (ReflectionException) {
            return null;
        }

        if ($implementsInterface) {
            return $instance;
        }

        return null;
    }
}
