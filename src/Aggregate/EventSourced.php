<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionNamedType;
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

    /**
     * @inheritDoc
     */
    public static function reconstituteFromHistory(Event ...$domainEvents)
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
    private static function stateClass(): string
    {
        $refObj = new ReflectionClass(static::class);

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
     * @inheritDoc
     */
    public static function reconstituteFromStateArray(array $state)
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
        $docBlockFactory = DocBlockFactory::createInstance();

        $reflectionClass = new ReflectionClass(self::stateClass());
        $reflectionProperties = $reflectionClass->getProperties();

        foreach ($reflectionProperties as $reflectionProperty) {
            $docBlock = $docBlockFactory->create($reflectionProperty);
            $tags = $docBlock->getTagsByName('ApiProperty');

            if (empty($tags)) {
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
