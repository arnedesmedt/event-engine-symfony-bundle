<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionProperty;

use function array_filter;
use function array_map;

class PropertyDocBlockExtractor
{
    private readonly DocBlockFactory $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /** @param class-string $class */
    public function propertyDocBlockFromClassAndProperty(string $class, string $property): DocBlock|null
    {
        $propertyReflection = PropertyReflection::propertyReflectionFromClassAndProperty($class, $property);

        if (! $propertyReflection instanceof ReflectionProperty) {
            return null;
        }

        try {
            return $this->docBlockFactory->create($propertyReflection);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param class-string $class
     *
     * @return array<DocBlock>
     */
    public function propertyTypeClassDocBlocksFromClassAndProperty(string $class, string $property): array
    {
        $typeReflectionClasses = PropertyReflection::propertyTypeReflectionClassesFromClassAndProperty(
            $class,
            $property,
        );

        return array_filter(
            array_map(
                function (ReflectionClass $typeReflectionClass): DocBlock|null {
                    try {
                        return $this->docBlockFactory->create($typeReflectionClass);
                    } catch (InvalidArgumentException) {
                        return null;
                    }
                },
                $typeReflectionClasses,
            ),
        );
    }
}
