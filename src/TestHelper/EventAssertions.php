<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\TestHelper;

use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Persistency\PDO;
use Closure;
use PDOStatement;
use Psr\Container\ContainerInterface;
use TeamBlue\TestRequest\TestRequest\TestRequest;
use TeamBlue\ValueObjects\StringValue;

use function array_map;
use function sprintf;

class EventAssertions
{
    /** @param array<class-string<Event>> $listOfNewEventClasses */
    public static function assertNewEventsForStream(
        string $streamName,
        StringValue $aggregateId,
        array $listOfNewEventClasses,
    ): Closure {
        return function (
            TestRequest $testRequest,
            ContainerInterface $container,
        ) use (
            $aggregateId,
            $listOfNewEventClasses,
            $streamName,
        ): void {
            /** @var PDO $connection */
            $connection = $container->get('event_engine.connection');
            /** @var PDOStatement $statement */
            $statement = $connection->prepare(
                sprintf(
                    'SELECT * FROM %s_stream 
                        WHERE 
                            metadata->>\'_aggregate_id\' = :aggregateId AND 
                            metadata->>\'_causation_id\' = :messageUuid 
                        ORDER BY created_at',
                    $streamName,
                ),
            );

            $statement->execute(
                [
                    'aggregateId' => $aggregateId->toString(),
                    'messageUuid' => $testRequest->requestId()->toString(),
                ],
            );
            $newEventsInDatabase = $statement->fetchAll(PDO::FETCH_ASSOC);
            $newEventClassesInDatabase = array_map(
                static fn (array $event) => $event['event_name'],
                $newEventsInDatabase,
            );

            $this->assertEquals($listOfNewEventClasses, $newEventClassesInDatabase);
        };
    }

    /** @param class-string<Event> $eventClass */
    public static function assertEventForStream(
        string $streamName,
        StringValue $aggregateId,
        string $eventClass,
        Closure $assertion,
    ): Closure {
        return static function (
            TestRequest $testRequest,
            ContainerInterface $container,
        ) use (
            $streamName,
            $aggregateId,
            $eventClass,
            $assertion,
        ): void {
            /** @var PDO $connection */
            $connection = $container->get('event_engine.connection');
            /** @var PDOStatement $statement */
            $statement = $connection->prepare(
                sprintf(
                    'SELECT * FROM %s_stream 
                        WHERE 
                            metadata->>\'_aggregate_id\' = :aggregateId AND 
                            metadata->>\'_causation_id\' = :messageUuid AND
                            event_name = :eventClass
                        ORDER BY created_at',
                    $streamName,
                ),
            );

            $statement->execute(
                [
                    'aggregateId' => $aggregateId->toString(),
                    'messageUuid' => $testRequest->requestId()->toString(),
                    'eventClass' => $eventClass,
                ],
            );
            $newEventInDatabase = $statement->fetch(PDO::FETCH_ASSOC);

            $assertion($newEventInDatabase);
        };
    }
}
