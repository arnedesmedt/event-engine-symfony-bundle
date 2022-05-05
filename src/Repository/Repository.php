<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\ValueObjects\ListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\DocumentStore\DocumentStore;
use LogicException;
use ReflectionClass;
use Throwable;

use function sprintf;

/**
 * @template T
 * @template TAgg
 * @extends DefaultStateRepository<T>
 * @implements AggregateRepository<T,TAgg>
 */
abstract class Repository extends DefaultStateRepository implements AggregateRepository
{
    /** @var class-string */
    protected string $aggregateClass;

    /**
     * @param class-string $documentStoreName
     * @param class-string $stateClass
     * @param class-string<ListValue<T>> $statesClass
     */
    public function __construct(
        DocumentStore $documentStore,
        string $documentStoreName,
        string $stateClass,
        string $statesClass
    ) {
        parent::__construct($documentStore, $documentStoreName, $stateClass, $statesClass);

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
