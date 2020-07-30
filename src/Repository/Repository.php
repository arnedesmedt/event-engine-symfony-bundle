<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\DocumentStore\Filter\AnyFilter;
use EventEngine\DocumentStore\Filter\Filter;
use EventEngine\DocumentStore\PartialSelect;
use PDO;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Traversable;

use function array_filter;
use function array_map;
use function array_values;
use function iterator_to_array;
use function sprintf;

class Repository
{
    public const DOCUMENT_STORE_NOT_FOUND = 'Could not found document store \'%s\' for repository \'%s\'.';

    protected DocumentStore $documentStore;
    protected string $documentStoreName;
    protected string $stateClass;
    protected PDO $connection;

    public function __construct(
        DocumentStore $documentStore,
        string $documentStoreName,
        string $stateClass,
        PDO $connection
    ) {
        $this->documentStore = $documentStore;
        $this->documentStoreName = $documentStoreName;
        $this->stateClass = $stateClass;
        $this->connection = $connection;
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

        return $this->stateClass::fromArray($document['state']) ?? null;
    }

    /**
     * @return Traversable<array<mixed>>
     */
    public function findDocuments(?Filter $filter = null, ?int $skip = null, ?int $limit = null): Traversable
    {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        return $this->documentStore->findDocs(
            $this->documentStoreName,
            $filter,
            $skip,
            $limit
        );
    }

    /**
     * @return Traversable<array<mixed>>
     */
    public function findPartialDocuments(
        PartialSelect $partialSelect,
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null
    ): Traversable {
        if ($filter === null) {
            $filter = new AnyFilter();
        }

        return $this->documentStore->findPartialDocs(
            $this->documentStoreName,
            $partialSelect,
            $filter,
            $skip,
            $limit
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
     * @return array<ImmutableRecord>
     */
    public function findDocumentStates(?Filter $filter = null, ?int $skip = null, ?int $limit = null): array
    {
        return $this->statesFromDocuments(
            $this->findDocuments($filter, $skip, $limit)
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
    ): ?ImmutableRecord {
        $document = $this->needDocument($identifier, $exception);

        return $this->stateFromDocument($document);
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
