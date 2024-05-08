<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use phpDocumentor\Reflection\DocBlock;
use Symfony\Component\PropertyInfo\PropertyDescriptionExtractorInterface;

use function array_filter;
use function array_map;
use function implode;

class PropertyDescriptionExtractor implements PropertyDescriptionExtractorInterface
{
    public function __construct(
        private readonly PropertyDocBlockExtractor $propertyDocBlockExtractor,
    ) {
    }

    /** @param class-string $class */
    public function fullDescriptionFromClassAndProperty(string $class, string $property): string|null
    {
        return $this->propertyFullDescription($class, $property)
            ?? $this->propertyTypeFullDescriptions($class, $property);
    }

    /** @param class-string $class */
    public function summaryFromClassAndProperty(string $class, string $property): string|null
    {
        return $this->propertySummary($class, $property)
            ?? $this->propertyTypeSummaries($class, $property);
    }

    /** @param class-string $class */
    public function descriptionFromClassAndProperty(string $class, string $property): string|null
    {
        return $this->propertyDescription($class, $property)
            ?? $this->propertyTypeDescriptions($class, $property);
    }

    /** @param class-string $class */
    public function propertyFullDescription(string $class, string $property): string|null
    {
        $docBlock = $this->propertyDocBlockExtractor->propertyDocBlockFromClassAndProperty($class, $property);

        return $this->fullDescriptionFromDocBlock($docBlock);
    }

    /** @param class-string $class */
    public function propertyDescription(string $class, string $property): string|null
    {
        $docBlock = $this->propertyDocBlockExtractor->propertyDocBlockFromClassAndProperty($class, $property);

        return $this->descriptionFromDocBlock($docBlock);
    }

    /** @param class-string $class */
    public function propertySummary(string $class, string $property): string|null
    {
        $docBlock = $this->propertyDocBlockExtractor->propertyDocBlockFromClassAndProperty($class, $property);

        return $this->summaryFromDocBlock($docBlock);
    }

    /** @param class-string $class */
    public function propertyTypeFullDescriptions(string $class, string $property): string|null
    {
        $docBlocks = $this->propertyDocBlockExtractor->propertyTypeClassDocBlocksFromClassAndProperty(
            $class,
            $property,
        );

        $descriptions = array_map(
            fn (DocBlock $docBlock) => $this->fullDescriptionFromDocBlock($docBlock),
            $docBlocks,
        );

        return implode('<br/>', array_filter($descriptions));
    }

    /** @param class-string $class */
    public function propertyTypeDescriptions(string $class, string $property): string|null
    {
        $docBlocks = $this->propertyDocBlockExtractor->propertyTypeClassDocBlocksFromClassAndProperty(
            $class,
            $property,
        );

        $descriptions = array_map(
            fn (DocBlock $docBlock) => $this->descriptionFromDocBlock($docBlock),
            $docBlocks,
        );

        return implode('<br/>', array_filter($descriptions));
    }

    /** @param class-string $class */
    public function propertyTypeSummaries(string $class, string $property): string|null
    {
        $docBlocks = $this->propertyDocBlockExtractor->propertyTypeClassDocBlocksFromClassAndProperty(
            $class,
            $property,
        );

        $descriptions = array_map(
            fn (DocBlock $docBlock) => $this->summaryFromDocBlock($docBlock),
            $docBlocks,
        );

        return implode('<br/>', array_filter($descriptions));
    }

    private function fullDescriptionFromDocBlock(DocBlock|null $docBlock): string|null
    {
        if (! $docBlock instanceof DocBlock) {
            return null;
        }

        $summary = $docBlock->getSummary();
        $description = $docBlock->getDescription()->render();

        if ($summary === '' && $description === '') {
            return null;
        }

        return implode(
            '<br/>',
            array_filter(
                [
                    $summary,
                    $description,
                ],
            ),
        );
    }

    private function descriptionFromDocBlock(DocBlock|null $docBlock): string|null
    {
        if (! $docBlock instanceof DocBlock) {
            return null;
        }

        $description = $docBlock->getDescription()->render();

        if ($description === '') {
            return null;
        }

        return $description;
    }

    private function summaryFromDocBlock(DocBlock|null $docBlock): string|null
    {
        if (! $docBlock instanceof DocBlock) {
            return null;
        }

        $summary = $docBlock->getSummary();

        if ($summary === '') {
            return null;
        }

        return $summary;
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $context
     */
    public function getShortDescription(string $class, string $property, array $context = []): string|null
    {
        return $this->summaryFromClassAndProperty($class, $property);
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $context
     */
    public function getLongDescription(string $class, string $property, array $context = []): string|null
    {
        return $this->descriptionFromClassAndProperty($class, $property);
    }
}
