<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Projector as ProjectorAttribute;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use ADS\Util\MetadataExtractor\MetadataExtractorAware;
use ReflectionClass;

class ProjectorExtractor
{
    use MetadataExtractorAware;

    /** @param ReflectionClass<object> $reflectionClass */
    public function nameFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var string $name */
        $name = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Projector> $class */
                Projector::class => static fn (string $class) => $class::projectionName(),
                ProjectorAttribute::class => static fn (ProjectorAttribute $projector) => $projector->name(),
            ],
        );

        return $name;
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function versionFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var string $version */
        $version = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Projector> $class */
                Projector::class => static fn (string $class) => $class::version(),
                ProjectorAttribute::class => static fn (ProjectorAttribute $projector) => $projector->version(),
            ],
        );

        return $version;
    }
}
