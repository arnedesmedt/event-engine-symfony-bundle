<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Store;

use EventEngine\Data\ImmutableRecord;
use EventEngine\EventStore\EventStore;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\GenericEvent;
use EventEngine\Messaging\MessageBag;
use EventEngine\Prooph\V7\EventStore\ProophEventStore;
use EventEngine\Runtime\FunctionalFlavour;
use EventEngine\Util\MapIterator;
use Iterator;

use function array_map;
use function method_exists;

class SensitiveEncryptedProophEventStore implements EventStore
{
    public function __construct(
        private ProophEventStore $proophEventStore,
        private FunctionalFlavour $functionalFlavour,
    ) {
    }

    public function createStream(string $streamName): void
    {
        $this->proophEventStore->createStream($streamName);
    }

    public function deleteStream(string $streamName): void
    {
        $this->proophEventStore->deleteStream($streamName);
    }

    public function appendTo(string $streamName, GenericEvent ...$events): void
    {
        $events = array_map(
            function (GenericEvent $event): GenericEvent {
                $customMessageInBag = $this->functionalFlavour->convertMessageReceivedFromNetwork($event);

                $message = $customMessageInBag->get(MessageBag::MESSAGE);
                if (! $message instanceof ImmutableRecord) {
                    return $event;
                }

                $messageData = method_exists($message, 'toSensitiveEncryptedArray')
                    ? $message->toSensitiveEncryptedArray()
                    : $message->toArray();

                $eventData = $event->toArray();
                $eventData['payload'] = $messageData;

                /** @var GenericEvent $sensitiveEncryptedGenericEvent */
                $sensitiveEncryptedGenericEvent = GenericEvent::fromArray($eventData);

                return $sensitiveEncryptedGenericEvent;
            },
            $events,
        );

        $this->proophEventStore->appendTo($streamName, ...$events);
    }

    /** @return Iterator<GenericEvent> */
    public function loadAggregateEvents(
        string $streamName,
        string $aggregateType,
        string $aggregateId,
        int $minVersion = 1,
        int|null $maxVersion = null,
    ): Iterator {
        return $this->decryptEncryptedEvents(
            $this->proophEventStore
                ->loadAggregateEvents(
                    $streamName,
                    $aggregateType,
                    $aggregateId,
                    $minVersion,
                    $maxVersion,
                ),
        );
    }

    /** @return Iterator<GenericEvent> */
    public function loadEventsByCorrelationId(
        string $streamName,
        string $correlationId,
    ): Iterator {
        return $this->decryptEncryptedEvents(
            $this->proophEventStore->loadEventsByCorrelationId(
                $streamName,
                $correlationId,
            ),
        );
    }

    /** @return Iterator<GenericEvent> */
    public function loadEventsByCausationId(
        string $streamName,
        string $causationId,
    ): Iterator {
        return $this->decryptEncryptedEvents(
            $this->proophEventStore->loadEventsByCausationId(
                $streamName,
                $causationId,
            ),
        );
    }

    /**
     * @param Iterator<GenericEvent> $events
     *
     * @return Iterator<GenericEvent>
     */
    private function decryptEncryptedEvents(Iterator $events): Iterator
    {
        return new MapIterator($events, static function (GenericEvent $event): GenericEvent {
            $eventName = $event->messageName();
            $eventData = $event->payload();
            /** @var JsonSchemaAwareRecord $decryptedEvent */
            $decryptedEvent = method_exists($eventName, 'fromEncryptedSensitiveData')
                ? $eventName::fromEncryptedSensitiveData($eventData)
                : $eventName::fromArray($eventData);

            $eventData = $event->toArray();
            $eventData['payload'] = $decryptedEvent->toArray();

            /** @var GenericEvent $decryptedGenericEvent */
            $decryptedGenericEvent = GenericEvent::fromArray($eventData);

            return $decryptedGenericEvent;
        });
    }
}
