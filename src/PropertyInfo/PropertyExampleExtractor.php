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

class PropertyExampleExtractor
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
    public function fromClassAndProperty(string $class, string $property): array
    {
        return $this->fromInterface($class, $property)
            ?? $this->fromPropertyDocBlock($class, $property)
            ?? $this->fromPropertyTypeClassDocBlocks($class, $property)
            ?? [];
    }

    /**
     * @param class-string $class
     *
     * @return array<string>|null
     */
    private function fromPropertyDocBlock(string $class, string $property): array|null
    {
        $docBlock = $this->propertyDocBlockExtractor->propertyDocBlockFromClassAndProperty($class, $property);

        $examples = $this->fromDocBlock($docBlock);

        return empty($examples) ? null : $examples;
    }

    /**
     * @param class-string $class
     *
     * @return array<string>|null
     */
    private function fromPropertyTypeClassDocBlocks(string $class, string $property): array|null
    {
        $docBlocksPerType = $this->propertyDocBlockExtractor->propertyTypeClassDocBlocksFromClassAndProperty(
            $class,
            $property,
        );

        $examples = array_reduce(
            $docBlocksPerType,
            fn (array $carry, DocBlock $docBlock) => [...$carry, ...$this->fromDocBlock($docBlock)],
            [],
        );

        return empty($examples) ? null : $examples;
    }

    /** @return array<string> */
    private function fromDocBlock(DocBlock|null $docBlock): array
    {
        $exampleTags = $docBlock?->getTagsByName('example') ?? [];

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
    private function fromInterface(string $class, string $property): array|null
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
