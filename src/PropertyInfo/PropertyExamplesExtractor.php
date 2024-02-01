<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use ADS\JsonImmutableObjects\HasPropertyExamples;
use ADS\ValueObjects\ValueObject;
use phpDocumentor\Reflection\DocBlock;
use ReflectionClass;

use function array_map;
use function array_reduce;
use function is_array;

class PropertyExamplesExtractor
{
    public function __construct(
        private readonly PropertyDocBlockExtractor $propertyDocBlockExtractor,
    ) {
    }

    /**
     * @param class-string $class
     *
     * @return array<string>
     */
    public function examplesFromClassAndProperty(string $class, string $property): array
    {
        return $this->examplesFromInterface($class, $property)
            ?? $this->examplesFromPropertyDocBlock($class, $property)
            ?? $this->examplesFromPropertyTypeClassDocBlocks($class, $property)
            ?? [];
    }

    /**
     * @param class-string $class
     *
     * @return array<string>|null
     */
    private function examplesFromPropertyDocBlock(string $class, string $property): array|null
    {
        $docBlock = $this->propertyDocBlockExtractor->propertyDocBlockFromClassAndProperty($class, $property);

        $examples = $this->examplesFromDocBlock($docBlock);

        return empty($examples) ? null : $examples;
    }

    /**
     * @param class-string $class
     *
     * @return array<string>|null
     */
    private function examplesFromPropertyTypeClassDocBlocks(string $class, string $property): array|null
    {
        $docBlocksPerType = $this->propertyDocBlockExtractor->propertyTypeClassDocBlocksFromClassAndProperty(
            $class,
            $property,
        );

        $examples = array_reduce(
            $docBlocksPerType,
            fn (array $carry, DocBlock $docBlock) => [...$carry, ...$this->examplesFromDocBlock($docBlock)],
            [],
        );

        return empty($examples) ? null : $examples;
    }

    /** @return array<string> */
    private function examplesFromDocBlock(DocBlock $docBlock): array
    {
        $exampleTags = $docBlock->getTagsByName('example');

        return array_map(
            static fn (DocBlock\Tag $exampleTag) => (string) $exampleTag,
            $exampleTags,
        );
    }

    /**
     * @param class-string $class
     *
     * @return array<string>|null
     */
    private function examplesFromInterface(string $class, string $property): array|null
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

        if (empty($examples)) {
            return null;
        }

        return array_map(
            static fn (mixed $example) => $example instanceof ValueObject ? $example->toValue() : $example,
            $examples,
        );
    }
}
