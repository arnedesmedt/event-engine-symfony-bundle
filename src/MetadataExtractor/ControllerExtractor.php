<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\ControllerCommand as ControllerCommandAttribute;
use ADS\Bundle\EventEngineBundle\Command\ControllerCommand;
use ReflectionClass;

class ControllerExtractor
{
    use ClassOrAttributeExtractor;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return class-string
     */
    public function fromReflectionClass(ReflectionClass $reflectionClass): string
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            ControllerCommand::class,
            ControllerCommandAttribute::class,
        );

        return $classOrAttributeInstance instanceof ControllerCommandAttribute
            ? $classOrAttributeInstance->controller()
            : $classOrAttributeInstance::__controller();
    }
}
