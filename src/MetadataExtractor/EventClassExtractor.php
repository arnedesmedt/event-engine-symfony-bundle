<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand as AggregateCommandAttribute;
use ADS\Bundle\EventEngineBundle\Attribute\Listener as ListenerAttribute;
use ADS\Bundle\EventEngineBundle\Attribute\Projector as ProjectorAttribute;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Event\Listener;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use ADS\Util\MetadataExtractor\MetadataExtractorAware;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;

class EventClassExtractor
{
    use MetadataExtractorAware;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>|class-string<JsonSchemaAwareRecord>
     */
    public function fromListenerReflectionClass(ReflectionClass $reflectionClass): array|string
    {
        /** @var array<class-string<JsonSchemaAwareRecord>>|class-string<JsonSchemaAwareRecord> $events */
        $events = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                ListenerAttribute::class => static fn (ListenerAttribute $attribute) => $attribute->eventsToHandle(),
                /** @param class-string<Listener> $class */
                Listener::class => static fn (string $class) => $class::__handleEvents(),
            ],
        );

        return $events;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>
     */
    public function fromProjectorReflectionClass(ReflectionClass $reflectionClass): array
    {
        /** @var array<class-string<JsonSchemaAwareRecord>> $events */
        $events = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                ProjectorAttribute::class => static fn (ProjectorAttribute $attribute) => $attribute->eventsToHandle(),
                /** @param class-string<Projector> $class */
                Projector::class => static fn (string $class) => $class::events(),
            ],
        );

        return $events;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>
     */
    public function fromAggregateCommandReflectionClass(ReflectionClass $reflectionClass): array
    {
        /** @var array<class-string<JsonSchemaAwareRecord>> $events */
        $events = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                AggregateCommandAttribute::class => static fn (
                    AggregateCommandAttribute $attribute,
                ) => $attribute->eventsToRecord(),
                /** @param class-string<AggregateCommand> $class */
                AggregateCommand::class => static fn (string $class) => $class::__eventsToRecord(),
            ],
        );

        return $events;
    }
}
