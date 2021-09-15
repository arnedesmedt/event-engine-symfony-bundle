<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

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
     * @param Traversable<array<mixed>> $documents
     *
     * @return array<ImmutableRecord>
     */
    public function statesFromDocuments(Traversable $documents): array
    {
        return array_filter(
            array_map(
                [$this, 'stateFromDocument'],
                array_values(iterator_to_array($documents))
            )
        );
    }

    /**
     * @param array<mixed>|null $document
     */
    public function stateFromDocument(?array $document): ?ImmutableRecord
    {
        if ($document === null) {
            return null;
        }

        self::checkDocumentHasState($document);

        return $this->stateClass::fromArray($document['state']);
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

    public function hasDocuments(?Filter $filter = null): bool
    {
        return $this->countDocuments($filter) > 0;
    }

    public function hasNoDocuments(?Filter $filter = null): bool
    {
        return $this->countDocuments($filter) === 0;
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
     */
    public function findDocumentState($identifier): ?ImmutableRecord
    {
        return $this->stateFromDocument(
            $this->findDocument($identifier)
        );
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function hasDocument($identifier): bool
    {
        $document = $this->findDocument($identifier);

        return $document !== null;
    }

    /**
     * @param ListValue|array<mixed> $identifiers
     */
    public function hasAllDocuments($identifiers): bool
    {
        if ($identifiers instanceof ListValue) {
            $identifiers = $identifiers->toArray();
        }

        if (! is_array($identifiers)) {
            throw new RuntimeException('List of identifiers is not an array.');
        }

        $filters = array_map(
            static fn ($identifier) => new DocIdFilter(
                $identifier instanceof ValueObject
                        ? $identifier->toValue()
                        : $identifier
            ),
            $identifiers
        );

        $documentIds = $this->documentStore->filterDocIds(
            $this->documentStoreName,
            new OrFilter(...$filters)
        );

        return count($identifiers) === count($documentIds);
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function hasNoDocument($identifier): bool
    {
        return ! $this->hasDocument($identifier);
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
     * @param string|ValueObject $identifier
     */
    public function dontNeedDocument(
        $identifier,
        ?Throwable $exception = null
    ): void {
        $document = $this->findDocument($identifier);

        $exception ??= new ConflictHttpException(
            sprintf(
                'Resource with id \'%s\' already exists in document store \'%s\'',
                (string) $identifier,
                $this->documentStoreName
            )
        );

        $this->checkDocumentDoesntExists(
            $document,
            $exception
        );
    }

    public function stateClass(): string
    {
        return $this->stateClass;
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
     * @param array<mixed> $document
     */
    private function checkDocumentDoesntExists(
        ?array $document,
        Throwable $exception
    ): void {
        if ($document !== null) {
            throw $exception;
        }
    }
}
