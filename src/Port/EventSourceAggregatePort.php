<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Port;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\Runtime\Oop\Port;
use RuntimeException;

use function get_debug_type;
use function lcfirst;
use function method_exists;
use function sprintf;

final class EventSourceAggregatePort implements Port
{
    /**
     * @param AggregateCommand $customCommand
     * @param array<int, class-string> $contextServices
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function callAggregateFactory(
        string $aggregateType,
        callable $aggregateFactory,
        $customCommand,
        ...$contextServices
    ): mixed {
        return $aggregateFactory($customCommand, ...$contextServices);
    }

    /**
     * @param AggregateRoot $aggregate
     * @param AggregateCommand $customCommand
     * @param array<int, class-string> $contextServices
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function callAggregateWithCommand(
        $aggregate,
        $customCommand,
        ...$contextServices
    ): void {
        $method = lcfirst($customCommand->__aggregateMethod());

        if (! method_exists($aggregate, $method)) {
            throw new RuntimeException(
                sprintf(
                    'Aggregate \'%s\' has no method \'%s\'.',
                    $aggregate::class,
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
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function popRecordedEvents($aggregate): array
    {
        if (! $aggregate instanceof AggregateRoot) {
            throw new RuntimeException(
                sprintf(
                    'Cannot apply event. Given aggregate is not an instance of \'%s\'. Got \'%s\'.',
                    AggregateRoot::class,
                    get_debug_type($aggregate)
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
                    get_debug_type($aggregate)
                )
            );
        }

        $aggregate->apply($customEvent);
    }

    /**
     * @return array<mixed>
     */
    public function serializeAggregate(mixed $aggregate): array
    {
        if (! $aggregate instanceof AggregateRoot) {
            throw new RuntimeException(
                sprintf(
                    'Cannot serialize aggregate. Given aggregate is not an instance of \'%s\'. Got \'%s\'',
                    AggregateRoot::class,
                    get_debug_type($aggregate)
                )
            );
        }

        return $aggregate->toArray();
    }

    /**
     * @param iterable<Event> $events
     */
    public function reconstituteAggregate(string $aggregateType, iterable $events): mixed
    {
        /** @var AggregateRoot $aggregateClass */
        $aggregateClass = $this->aggregateClassByType($aggregateType);

        return $aggregateClass::reconstituteFromHistory(...$events);
    }

    /**
     * @param array<mixed> $state
     */
    public function reconstituteAggregateFromStateArray(string $aggregateType, array $state, int $version): mixed
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
