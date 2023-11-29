<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Projecting\AggregateProjector;
use ReflectionClass;
use RuntimeException;

use function in_array;
use function reset;
use function sprintf;

abstract class DefaultProjector implements Projector
{
    public function __construct(protected DocumentStore $documentStore)
    {
    }

    public function prepareForRun(string $projectionVersion, string $projectionName): void
    {
        if ($this->documentStore->hasCollection(static::generateCollectionName($projectionVersion, $projectionName))) {
            return;
        }

        $this->documentStore->addCollection(static::generateCollectionName($projectionVersion, $projectionName));
    }

    public function deleteReadModel(string $projectionVersion, string $projectionName): void
    {
        $this->documentStore->dropCollection(static::generateCollectionName($projectionVersion, $projectionName));
    }

    public static function version(): string
    {
        return '0.1.0';
    }

    public static function generateOwnCollectionName(): string
    {
        return self::generateCollectionName(static::version(), static::projectionName());
    }

    public static function statesClass(): string
    {
        /** @var class-string<JsonSchemaAwareCollection> $statesClass */
        $statesClass = static::stateClass() . 's';

        return $statesClass;
    }

    protected static function generateCollectionName(string $projectionVersion, string $projectionName): string
    {
        return AggregateProjector::generateCollectionName($projectionVersion, $projectionName);
    }

    /**
     * @param object $event
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function handle(string $projectionVersion, string $projectionName, $event): void
    {
        $eventClass = $event::class;

        /** @var JsonSchemaAwareRecord|Event $eventMessage */
        $eventMessage = $event;
        if ($event instanceof Message) {
            $eventClass = $event->messageName();
            /** @var JsonSchemaAwareRecord|Event $eventMessage */
            $eventMessage = $event->get(MessageBag::MESSAGE);
        }

        if (! in_array($eventClass, static::events(), true)) {
            return;
        }

        $applyMethod = $this->applyMethod($eventMessage);

        $this->{$applyMethod}($eventMessage);
    }

    private function applyMethod(Event|JsonSchemaAwareRecord $event): string
    {
        if ($event instanceof Event) {
            return $event->__applyMethod();
        }

        $eventReflectionClass = new ReflectionClass($event);
        $eventReflectionAttributes = $eventReflectionClass->getAttributes(
            \ADS\Bundle\EventEngineBundle\Attribute\Event::class,
        );

        if (empty($eventReflectionAttributes)) {
            throw new RuntimeException(
                sprintf(
                    'Unable to apply event \'%s\'. Missing attribute \'%s\' in class \'%s\'.',
                    $event::class,
                    \ADS\Bundle\EventEngineBundle\Attribute\Event::class,
                    static::class,
                ),
            );
        }

        $eventReflectionAttribute = reset($eventReflectionAttributes);
        /** @var \ADS\Bundle\EventEngineBundle\Attribute\Event $eventAttribute */
        $eventAttribute = $eventReflectionAttribute->newInstance();

        return $eventAttribute->applyMethod();
    }
}
