<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;

use function method_exists;
use function sprintf;

/**
 * @template TState of JsonSchemaAwareRecord
 */
trait EventSourced
{
    /** @var Event[] */
    private array $recordedEvents = [];

    /** @var TState */
    private $state;

    /**
     * @return TState
     */
    public function state()
    {
        return $this->state;
    }

    public static function reconstituteFromHistory(Event ...$domainEvents): static
    {
        $self = new static();

        foreach ($domainEvents as $domainEvent) {
            $self->apply($domainEvent);
        }

        return $self;
    }

    /**
     * @return class-string<TState>
     */
    protected static function stateClass(): string
    {
        return EventEngineUtil::fromAggregateClassToStateClass(static::class);
    }

    /**
     * @inheritDoc
     */
    public static function reconstituteFromStateArray(array $state): static
    {
        $stateClass = static::stateClass();

        $self = new static();
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
                    $event::class,
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
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->state->toArray();
    }

    /**
     * Unused method for preprocessors. But it needs to be callable.
     */
    public function preProcessorAggregateMethod(): void
    {
    }

    public static function aggregateId(): string
    {
        $reflectionClass = new ReflectionClass(static::stateClass());
        $reflectionProperties = $reflectionClass->getProperties();

        foreach ($reflectionProperties as $reflectionProperty) {
            /** @var array<ReflectionAttribute> $attributes */
            $attributes = $reflectionProperty->getAttributes();

            $isIdentifier = false;
            foreach ($attributes as $attribute) {
                $arguments = $attribute->getArguments();
                $isIdentifier = $attribute->getName() === 'ApiPlatform\Metadata\ApiProperty'
                && ($arguments['identifier'] ?? false);

                if ($isIdentifier) {
                    break;
                }
            }

            if (! $isIdentifier) {
                continue;
            }

            // fixme ApiProperty can have multiple reasons of existence (not only the identifier)
            return $reflectionProperty->getName();
        }

        throw new RuntimeException(
            sprintf(
                'You have to override the aggregateId method for aggregate root \'%s\'.',
                static::class
            )
        );
    }
}
