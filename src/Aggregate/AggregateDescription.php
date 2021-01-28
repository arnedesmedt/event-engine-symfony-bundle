<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\Commanding\CommandProcessorDescription;
use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\Persistence\Stream;
use EventEngine\Runtime\Oop\FlavourHint;
use LogicException;
use ReflectionClass;
use RuntimeException;

use function array_key_exists;
use function array_map;
use function array_unique;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function sprintf;

abstract class AggregateDescription implements EventEngineDescription
{
    public static function describe(EventEngine $eventEngine): void
    {
        $usedAggregateRoots = [];
        foreach (static::commandAggregateMapping() as $commandClass => $aggregateRootClass) {
            $commandProcessor = $eventEngine->process($commandClass);

            if (self::handlePreprocessors($commandProcessor, $aggregateRootClass, $commandClass)) {
                continue;
            }

            $newAggregateRoot = self::newAggregateRoot($aggregateRootClass, $usedAggregateRoots);

            self::handleCommand($commandProcessor, $aggregateRootClass, $commandClass, $newAggregateRoot);
            self::handleEvents($commandProcessor, $commandClass);
            self::handleServices($commandProcessor, $commandClass);
            self::handleStorage($commandProcessor, $aggregateRootClass, $newAggregateRoot);
        }

        foreach (static::projectorsList() as $projectorClass) {
            $streams = self::streamsForProjector($projectorClass);

            $eventEngine->watch(...$streams)
                ->with($projectorClass::projectionName(), $projectorClass, $projectorClass::version())
                ->filterEvents($projectorClass::events());
        }
    }

    private static function handlePreProcessors(
        CommandProcessorDescription $commandProcessor,
        string $aggregateRootClass,
        string $commandClass
    ): bool {
        $preprocessor = static::commandPreprocessors()[$commandClass] ?? false;

        if (! $preprocessor) {
            return false;
        }

        $commandProcessor
            ->preProcess($preprocessor)
            ->withExisting($aggregateRootClass)
            ->identifiedBy(static::aggregateIdentifierMapping()[$aggregateRootClass])
            ->handle([FlavourHint::class, 'useAggregate']);

        return true;
    }

    /**
     * @param class-string $aggregateRootClass
     * @param array<class-string> $usedAggregateRoots
     */
    private static function newAggregateRoot(string $aggregateRootClass, array &$usedAggregateRoots): bool
    {
        $notFound = ! in_array($aggregateRootClass, $usedAggregateRoots);

        if ($notFound) {
            $usedAggregateRoots[] = $aggregateRootClass;
        }

        return $notFound;
    }

    private static function handleCommand(
        CommandProcessorDescription $commandProcessor,
        string $aggregateRootClass,
        string $commandClass,
        bool $newAggregateRoot
    ): void {
        $aggregateRootMethod = $newAggregateRoot ? 'withNew' : 'withExisting';

        $commandProcessor
            ->$aggregateRootMethod($aggregateRootClass)
            ->identifiedBy(static::aggregateIdentifierMapping()[$aggregateRootClass])
            ->handle(self::handle($aggregateRootClass, $commandClass, $newAggregateRoot));
    }

    private static function handleEvents(
        CommandProcessorDescription $commandProcessor,
        string $commandClass
    ): void {
        $events = static::commandEventMapping()[$commandClass] ?? [];

        if (! is_array($events)) {
            $events = [$events];
        }

        foreach ($events as $eventClass) {
            $commandProcessor
                ->recordThat($eventClass)
                ->apply([FlavourHint::class, 'useAggregate']);
        }
    }

    private static function handleServices(
        CommandProcessorDescription $commandProcessor,
        string $commandClass
    ): void {
        $services = static::commandServiceMapping()[$commandClass] ?? [];

        if (! is_array($services)) {
            $services = [$services];
        }

        foreach ($services as $serviceClass) {
            $commandProcessor->provideService($serviceClass);
        }
    }

    /**
     * @param class-string $aggregateRootClass
     */
    private static function handleStorage(
        CommandProcessorDescription $commandProcessor,
        string $aggregateRootClass,
        bool $newAggregateRoot
    ): void {
        if (! $newAggregateRoot) {
            return;
        }

        $aggregateName = EventEngineUtil::fromAggregateClassToAggregateName($aggregateRootClass);

        $commandProcessor
            ->storeEventsIn(EventEngineUtil::fromAggregateNameToStreamName($aggregateName))
            ->storeStateIn(EventEngineUtil::fromAggregateNameToDocumentStoreName($aggregateName));
    }

    /**
     * @param class-string $projectorClass
     *
     * @return array<Stream>
     */
    private static function streamsForProjector(string $projectorClass): array
    {
        $reflectionClassProjector = new ReflectionClass($projectorClass);

        if (! $reflectionClassProjector->implementsInterface(Projector::class)) {
            throw new LogicException(
                sprintf(
                    'The projector class %s doesn\'t implement the interface %s',
                    $projectorClass,
                    Projector::class
                )
            );
        }

        /** @var array<class-string> $aggregateRootClasses */
        $aggregateRootClasses = array_unique(
            array_map(
                static fn ($eventForProjector) => self::aggregateFromEvent($eventForProjector),
                $projectorClass::events()
            )
        );

        return array_map(
            static fn ($aggregateRootClass) => Stream::ofLocalProjection(
                EventEngineUtil::fromAggregateClassToStreamName($aggregateRootClass)
            ),
            $aggregateRootClasses
        );
    }

    /**
     * @param class-string $eventClass
     *
     * @return class-string
     */
    private static function aggregateFromEvent(string $eventClass): string
    {
        $commandEventMappings = static::commandEventMapping();
        $commandAggregateMappings = static::commandAggregateMapping();

        foreach ($commandEventMappings as $command => $eventClasses) {
            if (is_string($eventClasses)) {
                $eventClasses = [$eventClasses];
            }

            if (array_key_exists($command, $commandAggregateMappings) && in_array($eventClass, $eventClasses, true)) {
                return $commandAggregateMappings[$command];
            }
        }

        throw new LogicException(sprintf('Unable to find aggregate for event %s', $eventClass));
    }

    /**
     * @return array<string, string>
     */
    abstract protected static function aggregateIdentifierMapping(): array;

    /**
     * @return array<string, class-string<AggregateRoot>>
     */
    abstract protected static function commandAggregateMapping(): array;

    /**
     * @return array<string, array<class-string<Event>>>|array<string, class-string<Event>>
     */
    abstract protected static function commandEventMapping(): array;

    /**
     * @return array<string, array<class-string>>|array<string, class-string>
     */
    abstract protected static function commandServiceMapping(): array;

    /**
     * @return array<string, class-string>
     */
    abstract protected static function commandPreProcessors(): array;

    /**
     * @return array<int, class-string>
     */
    abstract protected static function projectorsList(): array;

    /**
     * @return array<string>
     */
    private static function handle(string $aggregateRootClass, string $commandClass, bool $newAggregateRoot): array
    {
        if (! $newAggregateRoot) {
            return [FlavourHint::class, 'useAggregate'];
        }

        $handle = [$aggregateRootClass, $commandClass::__aggregateMethod()];

        if (is_callable($handle)) {
            return $handle;
        }

        throw new RuntimeException(
            sprintf(
                'Aggregate method \'%s\' for aggregate root \'%s\' is not callable.',
                $commandClass::__aggregateMethod(),
                $aggregateRootClass
            )
        );
    }
}
