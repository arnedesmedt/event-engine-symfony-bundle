<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use JetBrains\PhpStorm\Deprecated;
use phpDocumentor\Reflection\DocBlock;

class PropertyDeprecationExtractor
{
    public function __construct(
        private readonly PropertyDocBlockExtractor $propertyDocBlockExtractor,
    ) {
    }

    /** @param class-string $class */
    public function fromClassAndProperty(string $class, string $property): string|null
    {
        return $this->fromPropertyDocBlock($class, $property)
            ?? $this->fromPropertyAttribute($class, $property);
    }

    /** @param class-string $class */
    private function fromPropertyDocBlock(string $class, string $property): string|null
    {
        $docBlock = $this->propertyDocBlockExtractor->propertyDocBlockFromClassAndProperty($class, $property);

        $examples = $this->fromDocBlock($docBlock);

        return empty($examples) ? null : $examples;
    }

    private function fromDocBlock(DocBlock|null $docBlock): string|null
    {
        if ($docBlock === null) {
            return null;
        }

        $deprecatedTags = $docBlock->getTagsByName('deprecated');

        if (empty($deprecatedTags)) {
            return null;
        }

        $deprecatedTag = $deprecatedTags[0];

        return (string) $deprecatedTag;
    }

    /** @param class-string $class */
    private function fromPropertyAttribute(string $class, string $property): string|null
    {
        $propertyReflection = PropertyReflection::propertyReflectionFromClassAndProperty($class, $property);
        $deprecatedAttributes = $propertyReflection?->getAttributes(Deprecated::class);

        if (empty($deprecatedAttributes)) {
            return null;
        }

        $deprecatedAttribute = $deprecatedAttributes[0];
        $reason = $deprecatedAttribute->getArguments()[0] ?? '';

        return empty($reason) ? 'deprecated' : $reason;
    }
}
