<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Message\Event;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

use function get_class;
use function method_exists;
use function sprintf;

trait EventSourced
{
    /** @var Event[] */
    private array $recordedEvents = [];

    /** @var mixed */
    private $state;

    public static function reconstituteFromHistory(Event ...$domainEvents): AggregateRoot
    {
        $self = new self();

        foreach ($domainEvents as $domainEvent) {
            $self->apply($domainEvent);
        }

        return $self;
    }

    /**
     * @return class-string
     */
    private static function stateClass(): string
    {
        $refObj = new ReflectionClass(self::class);

        /** @var ReflectionNamedType|null $returnType */
        $returnType = $refObj->getMethod('state')->getReturnType();

        if ($returnType === null) {
            throw new RuntimeException(
                sprintf(
                    'State method of aggregate \'%s\' must have a return type.',
                    self::class
                )
            );
        }

        return $returnType->getName();
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function reconstituteFromStateArray(array $state): AggregateRoot
    {
        $stateClass = self::stateClass();

        $self = new self();
        $self->state = $stateClass::fromArray($state);

        return $self;
    }

    public function recordThat(Event $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return Event[]
     */
    public function popRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function apply(Event $event): void
    {
        $whenMethod = $this->deriveMethodNameFromEvent($event);

        if (! method_exists($this, $whenMethod)) {
            throw new RuntimeException(
                sprintf(
                    'Unable to apply event \'%s\'. Missing method \'%s\' in class \'%s\'.',
                    get_class($event),
                    $whenMethod,
                    static::class
                )
            );
        }

        $this->{$whenMethod}($event);
    }

    private function deriveMethodNameFromEvent(Event $event): string
    {
        return $event->__applyMethod();
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->state->toArray();
    }
}
