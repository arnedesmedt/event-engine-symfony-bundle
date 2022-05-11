<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\ValueObjects\Implementation\ListValue\IterableListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use Throwable;

/**
 * @template TAgg of AggregateRoot
 * @template TStates of IterableListValue
 * @template TState of JsonSchemaAwareRecord
 * @extends StateRepository<TStates, TState>
 */
interface AggregateRepository extends StateRepository
{
    /**
     * @param array<string, mixed>|null $document
     *
     * @return TAgg|null
     */
    public function aggregateFromDocument(?array $document);

    /**
     * @return TAgg|null
     */
    public function findAggregate(string|ValueObject $identifier);

    /**
     * @return TAgg
     */
    public function needAggregate(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    );
}
