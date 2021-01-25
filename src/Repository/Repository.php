<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\ValueObjects\ValueObject;
use EventEngine\DocumentStore\DocumentStore;
use LogicException;
use PDO;
use ReflectionClass;
use Throwable;

use function assert;
use function sprintf;

abstract class Repository extends DefaultStateRepository implements AggregateRepository
{
    /** @var class-string */
    protected string $aggregateClass;

    /**
     * @param class-string $documentStoreName
     * @param class-string $stateClass
     */
    public function __construct(
        DocumentStore $documentStore,
        string $documentStoreName,
        string $stateClass,
        PDO $connection
    ) {
        parent::__construct($documentStore, $documentStoreName, $stateClass, $connection);

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
     * @param array<mixed>|null $document
     */
    public function aggregateFromDocument(?array $document): ?AggregateRoot
    {
        if ($document === null) {
            return null;
        }

        self::checkDocumentHasState($document);

        return $this->aggregateClass::reconstituteFromStateArray($document['state']);
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function findAggregate($identifier): ?AggregateRoot
    {
        return $this->aggregateFromDocument(
            $this->findDocument($identifier)
        );
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function needAggregate(
        $identifier,
        ?Throwable $exception = null
    ): AggregateRoot {
        $document = $this->needDocument($identifier, $exception);

        $aggregateRoot = $this->aggregateFromDocument($document);

        assert($aggregateRoot instanceof AggregateRoot);

        return $aggregateRoot;
    }
}
