<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use TeamBlue\ValueObjects\Implementation\ListValue\IterableListValue;
use TeamBlue\ValueObjects\ValueObject;
use Throwable;

/**
 * @template TAgg of AggregateRoot
 * @template TStates of IterableListValue
 * @template TState of JsonSchemaAwareRecord
 * @template TId of ValueObject
 * @template-extends StateRepository<TStates, TState, TId>
 */
interface AggregateRepository extends StateRepository
{
    /**
     * @param array<string, mixed>|null $document
     *
     * @return TAgg|null
     */
    public function aggregateFromDocumentState(array|null $document);

    /**
     * @param string|TId $identifier
     *
     * @return TAgg|null
     */
    public function findAggregate($identifier);

    /**
     * @param string|TId $identifier
     *
     * @return TAgg
     */
    public function needAggregate(
        $identifier,
        Throwable|null $exception = null,
    );
}
