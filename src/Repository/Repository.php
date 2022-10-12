<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\ValueObjects\Implementation\ListValue\IterableListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use LogicException;
use ReflectionClass;
use Throwable;

use function sprintf;

/**
 * @template TAgg of AggregateRoot
 * @template TStates of IterableListValue
 * @template TState of JsonSchemaAwareRecord
 * @template TId of ValueObject
 * @template-extends DefaultStateRepository<TStates, TState, TId>
 * @template-implements AggregateRepository<TAgg, TStates, TState, TId>
 */
abstract class Repository extends DefaultStateRepository implements AggregateRepository
{
    /** @var class-string<TAgg> */
    protected string $aggregateClass;

    /**
     * @param class-string $documentStoreName
     * @param class-string<TState> $stateClass
     * @param class-string<TStates> $statesClass
     */
    public function __construct(
        DocumentStore $documentStore,
        string $documentStoreName,
        string $stateClass,
        string $statesClass
    ) {
        parent::__construct($documentStore, $documentStoreName, $stateClass, $statesClass);

        /** @var class-string<TAgg> $aggregateClass */
        $aggregateClass = EventEngineUtil::fromStateToAggregateClass($this->stateClass);
        $reflectionClassAggregate = new ReflectionClass($aggregateClass);
        if (! $reflectionClassAggregate->implementsInterface(AggregateRoot::class)) {
            throw new LogicException(sprintf(
                'The aggregate class "%s" doesn\'t implement the "%s" interface',
                $aggregateClass,
                AggregateRoot::class
            ));
        }

        $this->aggregateClass = $aggregateClass;
    }

    /**
     * @inheritDoc
     */
    public function aggregateFromDocumentState(?array $documentState)
    {
        if ($documentState === null) {
            return null;
        }

        /** @var TAgg $aggregate */
        $aggregate = $this->aggregateClass::reconstituteFromStateArray($documentState);

        return $aggregate;
    }

    /**
     * @inheritDoc
     */
    public function findAggregate($identifier)
    {
        return $this->aggregateFromDocumentState(
            $this->findDocumentState($identifier)
        );
    }

    /**
     * @inheritDoc
     */
    public function needAggregate(
        $identifier,
        ?Throwable $exception = null
    ) {
        /** @var TAgg $aggregate */
        $aggregate = $this->aggregateFromDocumentState(
            $this->needDocumentState($identifier, $exception)
        );

        return $aggregate;
    }
}
