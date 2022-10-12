<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\Implementation\ListValue\IterableListValue;
use ADS\ValueObjects\ListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\DocumentStore\Filter\AnyFilter;
use EventEngine\DocumentStore\Filter\DocIdFilter;
use EventEngine\DocumentStore\Filter\Filter;
use EventEngine\DocumentStore\Filter\OrFilter;
use EventEngine\DocumentStore\OrderBy\OrderBy;
use EventEngine\DocumentStore\PartialSelect;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

use function array_map;
use function count;
use function iterator_to_array;
use function json_encode;
use function reset;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * @template TStates of IterableListValue
 * @template TState of JsonSchemaAwareRecord
 * @template TId of ValueObject
 * @template-implements StateRepository<TStates, TState, TId>
 */
abstract class DefaultStateRepository implements StateRepository
{
    /** @var class-string<TState> */
    protected string $stateClass;

    /** @var class-string<TStates> */
    protected string $statesClass;

    /**
     * @param class-string<TState> $stateClass
     * @param class-string<TStates> $statesClass
     */
    public function __construct(
        protected DocumentStore $documentStore,
        protected string $documentStoreName,
        string $stateClass,
        string $statesClass,
    ) {
        $reflectionClassState = new ReflectionClass($stateClass);
        if (! $reflectionClassState->implementsInterface(ImmutableRecord::class)) {
            throw new RuntimeException(sprintf(
                'The state class "%s" doesn\'t implement the "%s" interface',
                $stateClass,
                ImmutableRecord::class
            ));
        }

        $reflectionClassStates = new ReflectionClass($statesClass);
        if (! $reflectionClassStates->implementsInterface(ListValue::class)) {
            throw new RuntimeException(sprintf(
                'The states class "%s" doesn\'t implement the "%s" interface',
                $statesClass,
                ListValue::class
            ));
        }

        $this->stateClass = $stateClass;
        $this->statesClass = $statesClass;
    }

    /**
     * @inheritDoc
     */
    public function findDocument($identifier): ?array
    {
        /** @var array{state: array<string, mixed>}|null $document */
        $document = $this->documentStore->getDoc(
            $this->documentStoreName,
            (string) $identifier
        );

        return $document;
    }

    /**
     * @inheritDoc
     */
    public function findPartialDocument(PartialSelect $select, $identifier): mixed
    {
        return $this->documentStore->getPartialDoc(
            $this->documentStoreName,
            $select,
            (string) $identifier,
        );
    }

    /**
     * @inheritDoc
     */
    public function findDocumentState($identifier): ?array
    {
        /** @var array<string, mixed>|null $documentState */
        $documentState = $this->findPartialDocument(
            new PartialSelect([PartialSelect::MERGE_ALIAS => 'state']),
            $identifier,
        );

        return $documentState;
    }

    /**
     * @inheritDoc
     */
    public function findDocuments(
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null,
        ?OrderBy $orderBy = null
    ): array {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        $iterator = $this->documentStore->findDocs(
            $this->documentStoreName,
            $filter,
            $skip,
            $limit,
            $orderBy
        );

        return iterator_to_array($iterator);
    }

    /**
     * @inheritDoc
     */
    public function findPartialDocuments(
        PartialSelect $partialSelect,
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null,
        ?OrderBy $orderBy = null
    ): array {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        $iterator = $this->documentStore->findPartialDocs(
            $this->documentStoreName,
            $partialSelect,
            $filter,
            $skip,
            $limit,
            $orderBy
        );

        return iterator_to_array($iterator);
    }

    /**
     * @inheritDoc
     */
    public function findDocumentStates(
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null,
        ?OrderBy $orderBy = null
    ): array {
        /** @var array<array<string, mixed>> $documentStates */
        $documentStates = $this->findPartialDocuments(
            new PartialSelect([PartialSelect::MERGE_ALIAS => 'state']),
            $filter,
            $skip,
            $limit,
            $orderBy,
        );

        return $documentStates;
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
    public function upsertState($identifier, $state): void
    {
        $this->documentStore->upsertDoc(
            $this->documentStoreName,
            (string) $identifier,
            ['state' => $state->toArray()]
        );
    }

    /**
     * @inheritDoc
     */
    public function deleteDoc($identifier): void
    {
        $this->documentStore->deleteDoc(
            $this->documentStoreName,
            (string) $identifier
        );
    }

    /**
     * @inheritDoc
     */
    public function needDocument(
        $identifier,
        ?Throwable $exception = null
    ): array {
        $document = $this->findDocument($identifier);

        if ($document === null) {
            $exception ??= new NotFoundHttpException(
                sprintf(
                    'Resource with id \'%s\' not found in document store \'%s\'',
                    (string) $identifier,
                    $this->documentStoreName
                )
            );

            throw $exception;
        }

        return $document;
    }

    /**
     * @inheritDoc
     */
    public function needDocumentState(
        $identifier,
        ?Throwable $exception = null
    ): array {
        $state = $this->findDocumentState($identifier);

        if ($state === null) {
            $exception ??= new NotFoundHttpException(
                sprintf(
                    'Resource with id \'%s\' not found in document store \'%s\'',
                    (string) $identifier,
                    $this->documentStoreName
                )
            );

            throw $exception;
        }

        return $state;
    }

    /**
     * @inheritDoc
     */
    public function dontNeedDocument(
        $identifier,
        ?Throwable $exception = null
    ): void {
        try {
            $this->needDocument($identifier);
        } catch (NotFoundHttpException) {
            return;
        }

        $exception ??= new ConflictHttpException(
            sprintf(
                'Resource with id \'%s\' already exists in document store \'%s\'',
                (string) $identifier,
                $this->documentStoreName
            )
        );

        throw $exception;
    }

    /**
     * @inheritDoc
     */
    public function findDocumentsByIds(array|ListValue $identifiers): array
    {
        $filter = $this->identifiersToFilter($identifiers);

        return $filter === null
            ? []
            : $this->findDocuments($filter);
    }

    /**
     * @inheritDoc
     */
    public function needDocumentsByIds(array|ListValue $identifiers): array
    {
        $documents = $this->findDocumentsByIds($identifiers);

        if (count($documents) !== count($identifiers)) {
            throw new NotFoundHttpException(
                sprintf(
                    'One of the identifiers is not found: \'%s\'.',
                    json_encode($this->identifiersToScalars($identifiers), JSON_THROW_ON_ERROR)
                )
            );
        }

        return $documents;
    }

    /**
     * @inheritDoc
     */
    public function findDocumentStatesByIds(array|ListValue $identifiers): array
    {
        $filter = $this->identifiersToFilter($identifiers);

        return $filter === null
            ? []
            : $this->findDocumentStates($filter);
    }

    /**
     * @inheritDoc
     */
    public function needDocumentStatesByIds(array|ListValue $identifiers): array
    {
        $documentStates = $this->findDocumentStatesByIds($identifiers);

        if (count($documentStates) !== count($identifiers)) {
            throw new NotFoundHttpException(
                sprintf(
                    'One of the identifiers is not found: \'%s\'.',
                    json_encode($this->identifiersToScalars($identifiers), JSON_THROW_ON_ERROR)
                )
            );
        }

        return $documentStates;
    }

    /**
     * @inheritDoc
     */
    public function findStatesByIds(array|ListValue $identifiers)
    {
        return $this->statesFromDocuments(
            $this->findDocumentStatesByIds($identifiers)
        );
    }

    /**
     * @inheritDoc
     */
    public function needStatesByIds(array|ListValue $identifiers)
    {
        return $this->statesFromDocuments(
            $this->needDocumentStatesByIds($identifiers)
        );
    }

    /**
     * @inheritDoc
     */
    public function findStates(
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null,
        ?OrderBy $orderBy = null
    ) {
        return $this->statesFromDocuments(
            $this->findDocumentStates($filter, $skip, $limit, $orderBy)
        );
    }

    /**
     * @inheritDoc
     */
    public function findState($identifier)
    {
        $documentState = $this->findDocumentState($identifier);

        if ($documentState === null) {
            return null;
        }

        return $this->stateFromDocument($documentState);
    }

    /**
     * @inheritDoc
     */
    public function needState($identifier)
    {
        return $this->stateFromDocument(
            $this->needDocumentState($identifier)
        );
    }

    public function findDocumentIdValueObjects(?Filter $filter = null): ListValue
    {
        $documentIds = $this->findDocumentIds($filter);
        $identifiersClass = $this->identifiersClass();

        if ($identifiersClass === null) {
            throw new RuntimeException(
                sprintf('Could not found identifiers class for repository \'%s\'.', static::class)
            );
        }

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

    public function hasAllDocuments(array|ListValue $identifiers): bool
    {
        $filter = $this->identifiersToFilter($identifiers);

        $documentIds = $this->findDocumentIds($filter);

        return count($identifiers) === count($documentIds);
    }

    /**
     * phpcs:ignore Generic.Files.LineLength.TooLong
     * @param array<array<string, mixed>> $documentStates
     *
     * @return TStates
     */
    protected function statesFromDocuments(array $documentStates)
    {
        return $this->statesClass::fromArray($documentStates);
    }

    /**
     * @param array<string, mixed> $documentState
     *
     * @return TState
     */
    protected function stateFromDocument(array $documentState)
    {
        return $this->stateClass::fromArray($documentState);
    }

    /**
     * @return class-string<TState>
     */
    public function stateClass(): string
    {
        return $this->stateClass;
    }

    /**
     * @return class-string<ListValue<TId>>|null
     */
    protected function identifiersClass(): ?string
    {
        return null;
    }

    /**
     * @param array<string|TId>|ListValue<TId> $identifiers
     */
    private function identifiersToFilter(array|ListValue $identifiers): ?Filter
    {
        /** @var array<string> $scalarIdentifiers */
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
     * @param array<string|TId>|ListValue<TId> $identifiers
     *
     * @return array<mixed>
     */
    private function identifiersToScalars(array|ListValue $identifiers): array
    {
        if ($identifiers instanceof ListValue) {
            $identifiers = $identifiers->toArray();
        }

        return array_map(
            static fn ($identifier) => $identifier instanceof ValueObject
                ? $identifier->toValue()
                : $identifier,
            $identifiers
        );
    }
}
