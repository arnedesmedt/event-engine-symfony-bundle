<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use EventEngine\DocumentStore\Filter\Filter;
use EventEngine\DocumentStore\PartialSelect;
use Throwable;
use Traversable;

interface StateRepository
{
    /**
     * @param Traversable<array<mixed>> $documents
     *
     * @return array<ImmutableRecord>
     */
    public function statesFromDocuments(Traversable $documents): array;

    /**
     * @param array<mixed>|null $document
     */
    public function stateFromDocument(?array $document): ?ImmutableRecord;

    /**
     * @return Traversable<array<mixed>>
     */
    public function findDocuments(?Filter $filter = null, ?int $skip = null, ?int $limit = null): Traversable;

    /**
     * @return Traversable<array<mixed>>
     */
    public function findPartialDocuments(
        PartialSelect $partialSelect,
        ?Filter $filter = null,
        ?int $skip = null,
        ?int $limit = null
    ): Traversable;

    public function countDocuments(?Filter $filter = null): int;

    public function hasDocuments(?Filter $filter = null): bool;

    public function hasNoDocuments(?Filter $filter = null): bool;

    /**
     * @return array<ImmutableRecord>
     */
    public function findDocumentStates(?Filter $filter = null, ?int $skip = null, ?int $limit = null): array;

    /**
     * @param string|ValueObject $identifier
     *
     * @return array<mixed>
     */
    public function findDocument($identifier): ?array;

    /**
     * @param string|ValueObject $identifier
     */
    public function findDocumentState($identifier): ?ImmutableRecord;

    /**
     * @param string|ValueObject $identifier
     */
    public function hasDocument($identifier): bool;

    /**
     * @param string|ValueObject $identifier
     */
    public function hasNoDocument($identifier): bool;

    /**
     * @param string|ValueObject $identifier
     *
     * @return array<mixed>
     */
    public function needDocument(
        $identifier,
        ?Throwable $exception = null
    ): array;

    /**
     * @param string|ValueObject $identifier
     */
    public function needDocumentState(
        $identifier,
        ?Throwable $exception = null
    ): ImmutableRecord;

    /**
     * @param string|ValueObject $identifier
     */
    public function dontNeedDocument(
        $identifier,
        ?Throwable $exception = null
    ): void;

    /**
     * @return class-string
     */
    public function stateClass(): string;
}
