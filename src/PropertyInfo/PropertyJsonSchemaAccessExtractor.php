<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use EventEngine\Data\ImmutableRecord;
use ReflectionClass;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;

use function str_starts_with;

class PropertyJsonSchemaAccessExtractor implements PropertyAccessExtractorInterface
{
    /**
     * @param class-string $class
     * @param array<string, mixed> $context
     */
    public function isReadable(string $class, string $property, array $context = []): bool|null
    {
        return $this->isAccessible($class, $property);
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $context
     */
    public function isWritable(string $class, string $property, array $context = []): bool|null
    {
        return $this->isAccessible($class, $property);
    }

    /** @param class-string $class */
    private function isAccessible(string $class, string $property): bool|null
    {
        $reflectionClass = new ReflectionClass($class);

        if (! $reflectionClass->implementsInterface(ImmutableRecord::class)) {
            return null;
        }

        return ! str_starts_with($property, '__');
    }
}
