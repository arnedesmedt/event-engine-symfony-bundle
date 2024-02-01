<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use RuntimeException;

use function array_map;
use function sprintf;

class PropertyDocBlockExtractor
{
    private DocBlockFactory $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /** @param class-string $class */
    public function propertyDocBlockFromClassAndProperty(string $class, string $property): DocBlock
    {
        $reflectionProperty = PropertyTypeExtractor::propertyReflectionType($class, $property);

        if ($reflectionProperty === null) {
            throw new RuntimeException(
                sprintf(
                    'Property \'%s\' not found on class \'%s\'',
                    $property,
                    $class,
                ),
            );
        }

        return $this->docBlockFactory->create($reflectionProperty);
    }

    /**
     * @param class-string $class
     *
     * @return array<DocBlock>
     */
    public function propertyTypeClassDocBlocksFromClassAndProperty(string $class, string $property): array
    {
        $typeReflectionClasses = PropertyTypeExtractor::propertyReflectionClassesFromClassAndProperty(
            $class,
            $property,
        );

        return array_map(
            fn (ReflectionClass $typeReflectionClass) => $this->docBlockFactory->create($typeReflectionClass),
            $typeReflectionClasses,
        );
    }
}
