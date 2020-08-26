<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Message\Event;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\Runtime\Oop\FlavourHint;

use function in_array;
use function is_array;

abstract class AggregateDescription implements EventEngineDescription
{
    public static function describe(EventEngine $eventEngine): void
    {
        $usedAggregateRoots = [];
        foreach (static::commandAggregateMapping() as $commandClass => $aggregateRootClass) {
            $usedAggregateRoot = in_array($aggregateRootClass, $usedAggregateRoots);

            if (! $usedAggregateRoot) {
                $usedAggregateRoots[] = $aggregateRootClass;
            }

            $aggregateRootMethod = $usedAggregateRoot ? 'withExisting' : 'withNew';

            $commandProcessor = $eventEngine->process($commandClass);

            $commandProcessor
                ->$aggregateRootMethod($aggregateRootClass)
                ->identifiedBy(static::aggregateIdentifierMapping()[$aggregateRootClass])
                ->handle($usedAggregateRoot
                    ? [FlavourHint::class, 'useAggregate']
                    : [$aggregateRootClass, $commandClass::__aggregateMethod()]);

            $preprocessor = static::commandPreprocessors()[$commandClass] ?? false;

            if ($preprocessor) {
                $commandProcessor->preProcess($preprocessor);

                continue;
            }

            $events = static::commandEventMapping()[$commandClass] ?? [];

            if (! is_array($events)) {
                $events = [$events];
            }

            foreach ($events as $eventClass) {
                $commandProcessor
                    ->recordThat($eventClass)
                    ->apply([FlavourHint::class, 'useAggregate']);
            }

            $services = static::commandServiceMapping()[$commandClass] ?? [];

            if (! is_array($services)) {
                $services = [$services];
            }

            foreach ($services as $serviceClass) {
                $commandProcessor->provideService($serviceClass);
            }

            if ($usedAggregateRoot) {
                continue;
            }

            $aggregateName = EventEngineUtil::fromAggregateClassToAggregateName($aggregateRootClass);

            $commandProcessor
                ->storeEventsIn(EventEngineUtil::fromAggregateNameToStreamName($aggregateName))
                ->storeStateIn(EventEngineUtil::fromAggregateNameToDocumentStoreName($aggregateName));
        }
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
}
