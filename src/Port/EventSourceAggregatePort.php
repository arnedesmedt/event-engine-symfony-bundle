<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Port;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\Runtime\Oop\Port;
use RuntimeException;

use function get_class;
use function gettype;
use function is_object;
use function lcfirst;
use function method_exists;
use function sprintf;

final class EventSourceAggregatePort implements Port
{
    /**
     * @param AggregateCommand $customCommand
     * @param array<int, class-string> $contextServices
     *
     * @return mixed
     */
    public function callAggregateFactory(string $aggregateType, callable $aggregateFactory, $customCommand, ...$contextServices) // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    {
        return $aggregateFactory($customCommand, ...$contextServices);
    }

    /**
     * @param AggregateRoot $aggregate
     * @param AggregateCommand $customCommand
     * @param array<int, class-string> $contextServices
     */
    public function callAggregateWithCommand($aggregate, $customCommand, ...$contextServices): void // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    {
        $method = lcfirst($customCommand->__aggregateMethod());

        if (! method_exists($aggregate, $method)) {
            throw new RuntimeException(
                sprintf(
                    'Aggregate \'%s\' has no method \'%s\'.',
                    get_class($aggregate),
                    $method
                )
            );
        }

        $aggregate->{$method}($customCommand, ...$contextServices);
    }

    /**
     * @param mixed $aggregate
     *
     * @return array<Event>
     */
    public function popRecordedEvents($aggregate): array // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    {
        if (! $aggregate instanceof AggregateRoot) {
            throw new RuntimeException(
                sprintf(
                    'Cannot apply event. Given aggregate is not an instance of \'%s\'. Got \'%s\'.',
                    AggregateRoot::class,
                    is_object($aggregate) ? get_class($aggregate) : gettype($aggregate)
                )
            );
        }

        return $aggregate->popRecordedEvents();
    }

    /**
     * @inheritDoc
     */
    public function applyEvent($aggregate, $customEvent): void
    {
        if (! $aggregate instanceof AggregateRoot) {
            throw new RuntimeException(
                sprintf(
                    'Cannot apply event. Given aggregate is not an instance of \'%s\'. Got \'%s\'',
                    AggregateRoot::class,
                    is_object($aggregate) ? get_class($aggregate) : gettype($aggregate)
                )
            );
        }

        $aggregate->apply($customEvent);
    }

    /**
     * @param mixed $aggregate
     *
     * @return array<mixed>
     */
    public function serializeAggregate($aggregate): array
    {
        if (! $aggregate instanceof AggregateRoot) {
            throw new RuntimeException(
                sprintf(
                    'Cannot serialize aggregate. Given aggregate is not an instance of \'%s\'. Got \'%s\'',
                    AggregateRoot::class,
                    is_object($aggregate) ? get_class($aggregate) : gettype($aggregate)
                )
            );
        }

        return $aggregate->toArray();
    }

    /**
     * @param iterable<Event> $events
     *
     * @return mixed
     */
    public function reconstituteAggregate(string $aggregateType, iterable $events)
    {
        /** @var AggregateRoot $aggregateClass */
        $aggregateClass = $this->aggregateClassByType($aggregateType);

        return $aggregateClass::reconstituteFromHistory(...$events);
    }

    /**
     * @param array<mixed> $state
     *
     * @return mixed
     */
    public function reconstituteAggregateFromStateArray(string $aggregateType, array $state, int $version)
    {
        /** @var AggregateRoot $aggregateClass */
        $aggregateClass = $this->aggregateClassByType($aggregateType);

        return $aggregateClass::reconstituteFromStateArray($state);
    }

    protected function aggregateClassByType(string $aggregateType): string
    {
        return $aggregateType;
    }
}
