<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Request;

use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use ReflectionClass;

use function array_filter;
use function array_keys;
use function in_array;

trait DefaultRequest
{
    use JsonSchemaAwareRecordLogic;

    /**
     * @return array<string>
     *
     * @inheritDoc
     */
    private static function __optionalProperties(): array
    {
        $metadataProperties = [
            '__propTypeMap',
            '__schema',
            '__arrayPropItemTypeMap',
        ];

        return array_filter(
            array_keys(
                (new ReflectionClass(static::class))->getDefaultProperties()
            ),
            static fn (string $propertyName) => ! in_array($propertyName, $metadataProperties)
        );
    }
}
