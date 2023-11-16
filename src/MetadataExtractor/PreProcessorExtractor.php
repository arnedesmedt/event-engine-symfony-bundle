<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor as PreProcessorAttribute;
use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use ReflectionClass;

class PreProcessorExtractor
{
    use ClassOrAttributeExtractor;

    /** @param ReflectionClass<object> $reflectionClass */
    public function priorityFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            PreProcessor::class,
            PreProcessorAttribute::class,
        );

        return $classOrAttributeInstance instanceof PreProcessorAttribute
            ? $classOrAttributeInstance->priority()
            : 0;
    }
}
