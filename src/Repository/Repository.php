<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Util;
use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\DocumentStore\Filter\Filter;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Traversable;
use function array_filter;
use function array_map;
use function iterator_to_array;
use function sprintf;

class Repository
{
    public const DOCUMENT_STORE_NOT_FOUND = 'Could not found document store \'%s\' for repository \'%s\'.';

    private DocumentStore $documentStore;
    private string $documentStoreName;
    private string $stateClass;

    public function __construct(DocumentStore $documentStore, string $name, string $entityNamespace)
    {
        $this->documentStore = $documentStore;
        $this->documentStoreName = Util::fromAggregateNameToDocumentStoreName($name);
        $this->stateClass = Util::fromAggregateNameToStateClass($name, $entityNamespace);
    }

    /**
     * @param Traversable<array<mixed>> $documents
     *
     * @return array<ImmutableRecord>
     */
    public function statesFromDocuments(Traversable $documents) : array
    {
        return array_filter(
            array_map(
                [$this, 'stateFromDocument'],
                iterator_to_array($documents)
            )
        );
    }

    /**
     * @param array<mixed>|null $document
     */
    public function stateFromDocument(?array $document) : ?ImmutableRecord
    {
        if ($document === null) {
            return null;
        }

        return $this->stateClass::fromArray($document['state']) ?? null;
    }

    /**
     * @return Traversable<array<mixed>>
     */
    public function findDocuments(Filter $filter, ?int $skip = null, ?int $limit = null) : Traversable
    {
        return $this->documentStore->filterDocs(
            $this->documentStoreName,
            $filter,
            $skip,
            $limit
        );
    }

    /**
     * @return array<ImmutableRecord>
     */
    public function findDocumentStates(Filter $filter, ?int $skip = null, ?int $limit = null) : array
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
    public function findDocument($identifier) : ?array
    {
        return $this->documentStore->getDoc(
            $this->documentStoreName,
            (string) $identifier
        );
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function findDocumentState($identifier) : ?ImmutableRecord
    {
        return $this->stateFromDocument(
            $this->findDocument($identifier)
        );
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function hasDocument($identifier) : bool
    {
        $document = $this->findDocument($identifier);

        return $document !== null;
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function hasNoDocument($identifier) : bool
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
        ?string $message = null,
        string $exceptionClass = NotFoundHttpException::class
    ) : array {
        $document = $this->findDocument($identifier);

        $message = $message ?? sprintf(
            'Resource with id \'%s\' not found in document store \'%s\'',
            (string) $identifier,
            $this->documentStoreName
        );

        $this->checkDocumentExists(
            $document,
            $message,
            $exceptionClass
        );

        return (array) $document;
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function needDocumentState(
        $identifier,
        ?string $message = null,
        string $exceptionClass = NotFoundHttpException::class
    ) : ?ImmutableRecord {
        $document = $this->needDocument($identifier, $message, $exceptionClass);

        return $this->stateFromDocument($document);
    }

    /**
     * @param string|ValueObject $identifier
     */
    public function dontNeedDocument(
        $identifier,
        ?string $message = null,
        string $exceptionClass = ConflictHttpException::class
    ) : void {
        $document = $this->findDocument($identifier);

        $message = $message ?? sprintf(
            'Resource with id \'%s\' already exists in document store \'%s\'',
            (string) $identifier,
            $this->documentStoreName
        );

        $this->checkDocumentDoesntExists(
            $document,
            $message,
            $exceptionClass
        );
    }

    /**
     * @param array<mixed> $document
     * @param mixed $message
     */
    private function checkDocumentExists(
        ?array $document,
        $message,
        string $exceptionClass = NotFoundHttpException::class
    ) : void {
        if ($document === null) {
            throw new $exceptionClass($message);
        }
    }

    /**
     * @param array<mixed> $document
     * @param mixed $message
     */
    private function checkDocumentDoesntExists(
        ?array $document,
        $message,
        string $exceptionClass = ConflictHttpException::class
    ) : void {
        if ($document !== null) {
            throw new $exceptionClass($message);
        }
    }
}
