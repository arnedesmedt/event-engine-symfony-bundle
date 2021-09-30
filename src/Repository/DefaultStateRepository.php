<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\ListValue;
use ADS\ValueObjects\ValueObject;
use ArrayIterator;
use EventEngine\Data\ImmutableRecord;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\DocumentStore\Filter\AnyFilter;
use EventEngine\DocumentStore\Filter\DocIdFilter;
use EventEngine\DocumentStore\Filter\Filter;
use EventEngine\DocumentStore\Filter\OrFilter;
use EventEngine\DocumentStore\OrderBy\OrderBy;
use EventEngine\DocumentStore\PartialSelect;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Traversable;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function assert;
use function count;
use function is_array;
use function iterator_to_array;
use function json_encode;
use function reset;
use function sprintf;

abstract class DefaultStateRepository implements StateRepository
{
    public const DOCUMENT_STORE_NOT_FOUND = 'Could not found document store \'%s\' for repository \'%s\'.';

    /** @var class-string */
    protected string $stateClass;
    protected DocumentStore $documentStore;
    protected string $documentStoreName;

    /**
     * @param class-string $stateClass
     */
    public function __construct(
        DocumentStore $documentStore,
        string $documentStoreName,
        string $stateClass
    ) {
        $this->documentStore = $documentStore;
        $reflectionClassState = new ReflectionClass($stateClass);
        if (! $reflectionClassState->implementsInterface(ImmutableRecord::class)) {
            throw new LogicException(sprintf(
                'The state class "%s" doesn\'t implement the "%s" interface',
                $stateClass,
                ImmutableRecord::class
            ));
        }

        $this->stateClass = $stateClass;
        $this->documentStoreName = $documentStoreName;
    }

    /**
     * @param Traversable<array<mixed>>|array<array<mixed>> $documents
     *
     * @return array<ImmutableRecord>
     */
    private function statesFromDocuments($documents): array
    {
        if ($documents instanceof Traversable) {
            $documents = iterator_to_array($documents);
        }

        return array_filter(
            array_map(
                [$this, 'stateFromDocument'],
                array_values($documents)
            )
        );
    }

    /**
     * @param array<mixed>|null $document
     */
    private function stateFromDocument(?array $document): ?ImmutableRecord
    {
        if ($document === null) {
            return null;
        }

        self::checkDocumentHasState($document);

        return $this->stateClass::fromArray($document['state']);
    }

    /**
     * @param string|ValueObject $identifier
     *
     * @return array<mixed>
     */
    public function findDocument($identifier): ?array
    {
        return $this->documentStore->getDoc(
            $this->documentStoreName,
            (string) $identifier
        );
    }

    /**
     * @param string|ValueObject $identifier
     *
     * @return array<mixed>
     */
    public function needDocument(
        $identifier,
        ?Throwable $exception = null
    ): array {
        $document = $this->findDocument($identifier);

        $exception ??= new NotFoundHttpException(
            sprintf(
                'Resource with id \'%s\' not found in document store \'%s\'',
                (string) $identifier,
                $this->documentStoreName
            )
        );

        $this->checkDocumentExists(
            $document,
            $exception
        );

        return (array) $document;
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function dontNeedDocument(
        $identifier,
        ?Throwable $exception = null
    ): void {
        try {
            $document = $this->needDocument($identifier);
        } catch (NotFoundHttpException $exception) {
            return;
        }

        throw new ConflictHttpException(
            sprintf(
                'Resource with id \'%s\' already exists in document store \'%s\'',
                (string) $identifier,
                $this->documentStoreName
            )
        );
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function needDocumentState(
        $identifier,
        ?Throwable $exception = null
    ): ImmutableRecord {
        $document = $this->needDocument($identifier, $exception);

        $state = $this->stateFromDocument($document);

        assert($state instanceof ImmutableRecord);

        return $state;
    }

    /**
     * @return Traversable<array<mixed>>
     */
    public function findDocuments(
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null,
        ?OrderBy $orderBy = null
    ): Traversable {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        return $this->documentStore->findDocs(
            $this->documentStoreName,
            $filter,
            $skip,
            $limit,
            $orderBy
        );
    }

    /**
     * @inheritDoc
     */
    public function findDocumentsByIds($identifiers): Traversable
    {
        $filter = $this->identifiersToFilter($identifiers);

        if ($filter === null) {
            return new ArrayIterator();
        }

        return $this->findDocuments($filter);
    }

    /**
     * @inheritDoc
     */
    public function needDocumentsByIds($identifiers): array
    {
        $documents = $this->findDocumentsByIds($identifiers);
        $documentsArray = iterator_to_array($documents);
        $countIdentifiers = $identifiers instanceof ListValue
            ? $identifiers->count()
            : count($identifiers);

        if (count($documentsArray) !== $countIdentifiers) {
            $scalarIdentifiers = $this->identifiersToScalars($identifiers);

            throw new NotFoundHttpException(
                sprintf('One of the identifiers is not found: \'%s\'.', json_encode($scalarIdentifiers))
            );
        }

        return $documentsArray;
    }

    /**
     * @inheritDoc
     */
    public function findDocumentStatesByIds($identifiers): array
    {
        return $this->statesFromDocuments(
            $this->findDocumentsByIds($identifiers)
        );
    }

    /**
     * @inheritDoc
     */
    public function needDocumentStatesByIds($identifiers): array
    {
        return $this->statesFromDocuments(
            $this->needDocumentsByIds($identifiers)
        );
    }

    /**
     * @return Traversable<array<mixed>>
     */
    public function findPartialDocuments(
        PartialSelect $partialSelect,
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null,
        ?OrderBy $orderBy = null
    ): Traversable {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        return $this->documentStore->findPartialDocs(
            $this->documentStoreName,
            $partialSelect,
            $filter,
            $skip,
            $limit,
            $orderBy
        );
    }

    public function countDocuments(?Filter $filter = null): int
    {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        return $this->documentStore->countDocs(
            $this->documentStoreName,
            $filter
        );
    }

    /**
     * @inheritDoc
     */
    public function findDocumentIds(?Filter $filter = null): array
    {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        return $this->documentStore->filterDocIds(
            $this->documentStoreName,
            $filter
        );
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function findDocumentState($identifier): ?ImmutableRecord
    {
        return $this->stateFromDocument(
            $this->findDocument($identifier)
        );
    }

    /**
     * @return array<ImmutableRecord>
     */
    public function findDocumentStates(
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null,
        ?OrderBy $orderBy = null
    ): array {
        return $this->statesFromDocuments(
            $this->findDocuments($filter, $skip, $limit, $orderBy)
        );
    }

    public function findDocumentIdValueObjects(?Filter $filter = null): ListValue
    {
        $documentIds = $this->findDocumentIds($filter);

        if ($this->identifiersClass() === null) {
            throw new RuntimeException(
                sprintf('Could not found identifiers class for repository \'%s\'.', static::class)
            );
        }

        $identifiersClass = $this->identifiersClass();

        return $identifiersClass::fromArray($documentIds);
    }

    public function hasDocuments(?Filter $filter = null): bool
    {
        return $this->countDocuments($filter) > 0;
    }

    public function hasNoDocuments(?Filter $filter = null): bool
    {
        return $this->countDocuments($filter) === 0;
    }

    /**
     * @inheritDoc
     */
    public function hasDocument($identifier): bool
    {
        $document = $this->findDocument($identifier);

        return $document !== null;
    }

    /**
     * @inheritDoc
     */
    public function hasNoDocument($identifier): bool
    {
        return ! $this->hasDocument($identifier);
    }

    /**
     * @inheritDoc
     */
    public function hasAllDocuments($identifiers): bool
    {
        $filter = $this->identifiersToFilter($identifiers);

        $documentIds = $this->findDocumentIds($filter);

        return count($identifiers) === count($documentIds);
    }

    /**
     * @param array<mixed> $document
     */
    protected static function checkDocumentHasState(array $document): void
    {
        if (! array_key_exists('state', $document)) {
            throw new RuntimeException(
                sprintf(
                    'No state key found in document: \'%s\'',
                    json_encode($document)
                )
            );
        }
    }

    /**
     * @param array<mixed> $document
     */
    private function checkDocumentExists(
        ?array $document,
        Throwable $exception
    ): void {
        if ($document === null) {
            throw $exception;
        }
    }

    /**
     * @param array<mixed>|ListValue $identifiers
     */
    private function identifiersToFilter($identifiers): ?Filter
    {
        $scalarIdentifiers = $this->identifiersToScalars($identifiers);

        if (empty($scalarIdentifiers)) {
            return null;
        }

        $filters = array_map(
            static fn ($scalarIdentifier) => new DocIdFilter($scalarIdentifier),
            $scalarIdentifiers
        );

        return count($filters) === 1 ? reset($filters) : new OrFilter(...$filters);
    }

    /**
     * @param array<mixed>|ListValue $identifiers
     *
     * @return array<mixed>
     */
    private function identifiersToScalars($identifiers): array
    {
        if ($identifiers instanceof ListValue) {
            $identifiers = $identifiers->toArray();
        }

        if (! is_array($identifiers)) {
            throw new RuntimeException('List of identifiers is not an array.');
        }

        if (empty($identifiers)) {
            return $identifiers;
        }

        return array_map(
            static fn ($identifier) => $identifier instanceof ValueObject
                    ? $identifier->toValue()
                    : $identifier,
            $identifiers
        );
    }

    public function stateClass(): string
    {
        return $this->stateClass;
    }

    /**
     * @return class-string|null
     */
    protected function identifiersClass(): ?string
    {
        return null;
    }
}
