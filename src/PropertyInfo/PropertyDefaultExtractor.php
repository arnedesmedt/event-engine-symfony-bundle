<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use ADS\Util\ScalarUtil;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;

use function method_exists;

class PropertyDefaultExtractor
{
    /** @param class-string $class */
    public function fromClassAndProperty(string $class, string $property): mixed
    {
        $reflectionClass = new ReflectionClass($class);

        $defaultProperties = $reflectionClass->getDefaultProperties();

        if (isset($defaultProperties[$property])) {
            return $defaultProperties[$property];
        }

        if (
            ! $reflectionClass->implementsInterface(JsonSchemaAwareRecord::class)
            && ! method_exists($class, '__defaultProperties')
        ) {
            return null;
        }

        $metadataDefaultProperties = $class::__defaultProperties();

        if (isset($metadataDefaultProperties[$property])) {
            return ScalarUtil::toScalar($metadataDefaultProperties[$property]);
        }

        return null;
    }
}
