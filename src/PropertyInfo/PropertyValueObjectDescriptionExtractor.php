<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\PropertyInfo\PropertyDescriptionExtractorInterface;

use function reset;
use function sprintf;

class PropertyValueObjectDescriptionExtractor implements PropertyDescriptionExtractorInterface
{
    /**
     * @param class-string $class
     * @param array<string, mixed> $context
     */
    public function getShortDescription(string $class, string $property, array $context = []): string|null
    {
        $docBlock = $this->docBlock($class, $property);

        return $docBlock?->getSummary();
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $context
     */
    public function getLongDescription(string $class, string $property, array $context = []): string|null
    {
        $docBlock = $this->docBlock($class, $property);
        $description = $docBlock?->getDescription()->render();

        return sprintf(
            '%s%s',
            $docBlock?->getSummary() ?? '',
            ! empty($description) ? "\n" . $description : '',
        );
    }

    /** @param class-string $class */
    private function docBlock(string $class, string $property): DocBlock|null
    {
        $propertyTypeReflectionClasses = PropertyTypeExtractor::propertyReflectionClassesFromClassAndProperty(
            $class,
            $property,
        );

        if (empty($propertyTypeReflectionClasses)) {
            return null;
        }

        $firstPropertyTypeReflectionClass = reset($propertyTypeReflectionClasses);

        try {
            return DocBlockFactory::createInstance()->create($firstPropertyTypeReflectionClass);
        } catch (InvalidArgumentException) {
        }

        return null;
    }
}
