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
     * @return array<mixed>
     */
    public function findDocument(string|ValueObject $identifier): ?array;

    /**
     * @return array<mixed>
     */
    public function needDocument(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    ): array;

    public function dontNeedDocument(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    ): void;

    public function needDocumentState(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    ): ImmutableRecord;

    /**
     * @return Traversable<array<mixed>>
     */
    public function findDocuments(?Filter $filter = null, ?int $skip = null, ?int $limit = null): Traversable;

    /**
     * @param array<string>|ListValue $identifiers
     *
     * @return Traversable<array<string, mixed>>
     */
    public function findDocumentsByIds(array|ListValue $identifiers): Traversable;

    /**
     * @param array<string>|ListValue $identifiers
     *
     * @return array<array<string, mixed>>
     */
    public function needDocumentsByIds(array|ListValue $identifiers): array;

    /**
     * @param array<string>|ListValue $identifiers
     *
     * @return array<ImmutableRecord>
     */
    public function findDocumentStatesByIds(array|ListValue $identifiers): array;

    /**
     * @param array<string>|ListValue $identifiers
     *
     * @return array<ImmutableRecord>
     */
    public function needDocumentStatesByIds(array|ListValue $identifiers): array;

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

    public function findDocumentState(string|ValueObject $identifier): ?ImmutableRecord;

    /**
     * @return array<ImmutableRecord>
     */
    public function findDocumentStates(?Filter $filter = null, ?int $skip = null, ?int $limit = null): array;

    public function hasDocuments(?Filter $filter = null): bool;

    public function hasNoDocuments(?Filter $filter = null): bool;

    public function hasDocument(string|ValueObject $identifier): bool;

    public function hasNoDocument(string|ValueObject $identifier): bool;

    /**
     * @param array<string>|ListValue $identifiers
     */
    public function hasAllDocuments(array|ListValue $identifiers): bool;

    /**
     * @return class-string
     */
    public function stateClass(): string;
}
