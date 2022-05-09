<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\ValueObjects\ValueObject;
use EventEngine\DocumentStore\DocumentStore;
use LogicException;
use ReflectionClass;
use Throwable;

use function sprintf;

/**
 * @template TAgg
 * @template TStates
 * @template TState
 * @extends DefaultStateRepository<TStates, TState>
 * @implements AggregateRepository<TAgg,TStates, TState>
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
    public function aggregateFromDocument(?array $document)
    {
        if ($document === null) {
            return null;
        }

        self::checkDocumentHasState($document);

        return $this->aggregateClass::reconstituteFromStateArray($document['state']);
    }

    /**
     * @inheritDoc
     */
    public function findAggregate(string|ValueObject $identifier)
    {
        return $this->aggregateFromDocument(
            $this->findDocument($identifier)
        );
    }

    /**
     * @inheritDoc
     */
    public function needAggregate(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    ) {
        $document = $this->needDocument($identifier, $exception);

        /** @var TAgg $aggregateRoot */
        $aggregateRoot = $this->aggregateFromDocument($document);

        return $aggregateRoot;
    }
}
