<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Projector as ProjectorAttribute;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use ADS\Util\MetadataExtractor\MetadataExtractor;
use ReflectionClass;

class ProjectorExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function nameFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var string $name */
        $name = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Projector> $class */
                Projector::class => static fn (string $class) => $class::projectionName(),
                ProjectorAttribute::class => static fn (ProjectorAttribute $projector): string => $projector->name(),
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
                ProjectorAttribute::class => static fn (ProjectorAttribute $projector): string => $projector->version(),
            ],
        );

        return $version;
    }
}
