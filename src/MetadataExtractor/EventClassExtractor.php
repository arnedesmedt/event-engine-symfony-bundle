<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand as AggregateCommandAttribute;
use ADS\Bundle\EventEngineBundle\Attribute\Listener as ListenerAttribute;
use ADS\Bundle\EventEngineBundle\Attribute\Projector as ProjectorAttribute;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Event\Listener;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;

class EventClassExtractor
{
    use ClassOrAttributeExtractor;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>|class-string<JsonSchemaAwareRecord>
     */
    public function fromListenerReflectionClass(ReflectionClass $reflectionClass): array|string
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Listener::class,
            ListenerAttribute::class,
        );

        return $classOrAttributeInstance instanceof ListenerAttribute
            ? $classOrAttributeInstance->eventsToHandle()
            : $classOrAttributeInstance::__handleEvents();
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>
     */
    public function fromProjectorReflectionClass(ReflectionClass $reflectionClass): array
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Projector::class,
            ProjectorAttribute::class,
        );

        return $classOrAttributeInstance instanceof ProjectorAttribute
            ? $classOrAttributeInstance->eventsToHandle()
            : $classOrAttributeInstance::events();
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>
     */
    public function fromAggregateCommandReflectionClass(ReflectionClass $reflectionClass): array
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            AggregateCommand::class,
            AggregateCommandAttribute::class,
        );

        return $classOrAttributeInstance instanceof AggregateCommandAttribute
            ? $classOrAttributeInstance->eventsToRecord()
            : $classOrAttributeInstance::__eventsToRecord();
    }
}
