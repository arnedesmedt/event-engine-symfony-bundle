<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\ControllerCommand as ControllerCommandAttribute;
use ADS\Bundle\EventEngineBundle\Command\ControllerCommand;
use ADS\Util\MetadataExtractor\MetadataExtractorAware;
use ReflectionClass;

class ControllerExtractor
{
    use MetadataExtractorAware;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return class-string
     */
    public function fromReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var class-string $controller */
        $controller = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                ControllerCommandAttribute::class => static fn (
                    ControllerCommandAttribute $attribute,
                ) => $attribute->controller(),
                /** @param class-string<ControllerCommand> $class */
                ControllerCommand::class => static fn (string $class) => $class::__controller(),
            ],
        );

        return $controller;
    }
}
