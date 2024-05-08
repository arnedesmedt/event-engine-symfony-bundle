<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use ADS\JsonImmutableObjects\HasPropertyExamples;
use ADS\ValueObjects\ValueObject;
use phpDocumentor\Reflection\DocBlock;
use ReflectionClass;

use function array_filter;
use function array_reduce;
use function is_array;
use function reset;

class PropertyExampleExtractor
{
    public function __construct(
        private readonly PropertyDocBlockExtractor $propertyDocBlockExtractor,
    ) {
    }

    /** @param class-string $class */
    public function fromClassAndProperty(string $class, string $property): mixed
    {
        return $this->fromInterface($class, $property)
            ?? $this->fromPropertyDocBlock($class, $property)
            ?? $this->fromPropertyTypeClassDocBlocks($class, $property);
    }

    /** @param class-string $class */
    private function fromPropertyDocBlock(string $class, string $property): string|null
    {
        $docBlock = $this->propertyDocBlockExtractor->propertyDocBlockFromClassAndProperty($class, $property);

        return $this->fromDocBlock($docBlock);
    }

    /** @param class-string $class */
    private function fromPropertyTypeClassDocBlocks(string $class, string $property): string|null
    {
        $docBlocksPerType = $this->propertyDocBlockExtractor->propertyTypeClassDocBlocksFromClassAndProperty(
            $class,
            $property,
        );

        $examples = array_reduce(
            $docBlocksPerType,
            function (array $carry, DocBlock $docBlock) {
                $carry[] = $this->fromDocBlock($docBlock);

                return $carry;
            },
            [],
        );

        $nonEmptyExamples = array_filter($examples);

        return $examples === [] ? null : (string) reset($nonEmptyExamples);
    }

    private function fromDocBlock(DocBlock|null $docBlock): string|null
    {
        $exampleTags = $docBlock?->getTagsByName('example') ?? [];

        if ($exampleTags === []) {
            return null;
        }

        $exampleTag = reset($exampleTags);

        return (string) $exampleTag;
    }

    /** @param class-string $class */
    private function fromInterface(string $class, string $property): mixed
    {
        $reflectionClass = new ReflectionClass($class);
        if (! $reflectionClass->implementsInterface(HasPropertyExamples::class)) {
            return null;
        }

        $propertyExamples = $class::examples();
        $propertyExample = $propertyExamples[$property] ?? null;

        if ($propertyExample === null) {
            return null;
        }

        $examples = is_array($propertyExample) ? $propertyExample : [$propertyExample];

        if ($examples === []) {
            return null;
        }

        $example = reset($examples);

        return $example instanceof ValueObject ? $example->toValue() : $example;
    }
}
