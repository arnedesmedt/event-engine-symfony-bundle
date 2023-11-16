<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Projecting\AggregateProjector;
use RuntimeException;

use function in_array;
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

        if ($event instanceof Message) {
            $eventClass = $event->messageName();
            $event = $event->get(MessageBag::MESSAGE);
        }

        if (! in_array($eventClass, static::events(), true)) {
            return;
        }

        if (! $event instanceof Event) {
            throw new RuntimeException(
                sprintf(
                    'The event \'%s\' needs to implement the \'%s\' interface, ' .
                    'if you want to use it in projections.',
                    $eventClass,
                    Event::class,
                ),
            );
        }

        $applyMethod = $event->__applyMethod();

        $this->{$applyMethod}($event);
    }
}
