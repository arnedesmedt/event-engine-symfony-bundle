<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor as PreProcessorAttribute;
use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use ADS\Util\MetadataExtractor\MetadataExtractor;
use ReflectionClass;

class PreProcessorExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function priorityFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        /** @var int $priority */
        $priority = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                PreProcessorAttribute::class => static fn (PreProcessorAttribute $attribute) => $attribute->priority(),
                /** @param class-string<PreProcessor> $class */
                PreProcessor::class => static fn (string $class) => 0,
            ],
        );

        return $priority;
    }
}
