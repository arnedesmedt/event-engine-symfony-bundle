<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\ListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\Data\ImmutableRecord;
use EventEngine\DocumentStore\Filter\Filter;
use EventEngine\DocumentStore\PartialSelect;
use Throwable;
use Traversable;

interface StateRepository
{
    /**
     * @param string|ValueObject $identifier
     *
     * @return array<mixed>
     */
    public function findDocument($identifier): ?array;

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
    public function dontNeedDocument(
        $identifier,
        ?Throwable $exception = null
    ): void;

    /**
     * @param string|ValueObject $identifier
     */
    public function needDocumentState(
        $identifier,
        ?Throwable $exception = null
    ): ImmutableRecord;

    /**
     * @return Traversable<array<mixed>>
     */
    public function findDocuments(?Filter $filter = null, ?int $skip = null, ?int $limit = null): Traversable;

    /**
     * @param array<mixed>|ListValue $identifiers
     *
     * @return Traversable<mixed>
     */
    public function findDocumentsByIds($identifiers): Traversable;

    /**
     * @param array<mixed>|ListValue $identifiers
     *
     * @return array<mixed>
     */
    public function needDocumentsByIds($identifiers): array;

    /**
     * @param array<mixed>|ListValue $identifiers
     *
     * @return array<ImmutableRecord>
     */
    public function findDocumentStatesByIds($identifiers): array;

    /**
     * @param array<mixed>|ListValue $identifiers
     *
     * @return array<ImmutableRecord>
     */
    public function needDocumentStatesByIds($identifiers): array;

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

    /**
     * @return array<mixed>
     */
    public function findDocumentIds(?Filter $filter = null): array;

    /**
     * @param string|ValueObject $identifier
     */
    public function findDocumentState($identifier): ?ImmutableRecord;

    /**
     * @return array<ImmutableRecord>
     */
    public function findDocumentStates(?Filter $filter = null, ?int $skip = null, ?int $limit = null): array;

    public function hasDocuments(?Filter $filter = null): bool;

    public function hasNoDocuments(?Filter $filter = null): bool;

    /**
     * @param string|ValueObject $identifier
     */
    public function hasDocument($identifier): bool;

    /**
     * @param string|ValueObject $identifier
     */
    public function hasNoDocument($identifier): bool;

    /**
     * @param ListValue|array<mixed> $identifiers
     */
    public function hasAllDocuments($identifiers): bool;

    /**
     * @return class-string
     */
    public function stateClass(): string;
}
