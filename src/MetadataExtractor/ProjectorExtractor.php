<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Projector as ProjectorAttribute;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use ReflectionClass;

class ProjectorExtractor
{
    use ClassOrAttributeExtractor;

    /** @param ReflectionClass<object> $reflectionClass */
    public function nameFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Projector::class,
            ProjectorAttribute::class,
        );

        return $classOrAttributeInstance instanceof ProjectorAttribute
            ? $classOrAttributeInstance->name()
            : $classOrAttributeInstance::projectionName();
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function versionFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Projector::class,
            ProjectorAttribute::class,
        );

        return $classOrAttributeInstance instanceof ProjectorAttribute
            ? $classOrAttributeInstance->version()
            : $classOrAttributeInstance::version();
    }
}
