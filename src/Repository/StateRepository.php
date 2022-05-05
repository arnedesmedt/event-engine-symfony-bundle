<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\ListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\DocumentStore\Filter\Filter;
use EventEngine\DocumentStore\PartialSelect;
use Throwable;
use Traversable;

/**
 * @template T
 */
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

    /**
     * @return T
     */
    public function needDocumentState(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    );

    /**
     * @return Traversable<array<mixed>>
     */
    public function findDocuments(?Filter $filter = null, ?int $skip = null, ?int $limit = null): Traversable;

    /**
     * @param array<string>|ListValue<ValueObject|string|int> $identifiers
     *
     * @return Traversable<array{state: array<string, mixed>}>
     */
    public function findDocumentsByIds(array|ListValue $identifiers): Traversable;

    /**
     * @param array<string>|ListValue<ValueObject|string|int> $identifiers
     *
     * @return array<array{state: array<string, mixed>}>
     */
    public function needDocumentsByIds(array|ListValue $identifiers): array;

    /**
     * @param array<string>|ListValue<ValueObject|string|int> $identifiers
     *
     * @return ListValue<T>
     */
    public function findDocumentStatesByIds(array|ListValue $identifiers): ListValue;

    /**
     * @param array<string>|ListValue<ValueObject|string|int> $identifiers
     *
     * @return ListValue<T>
     */
    public function needDocumentStatesByIds(array|ListValue $identifiers): ListValue;

    /**
     * @return Traversable<array{state: array<string, mixed>}>
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
     * @return T|null
     */
    public function findDocumentState(string|ValueObject $identifier);

    /**
     * @return ListValue<T>
     */
    public function findDocumentStates(?Filter $filter = null, ?int $skip = null, ?int $limit = null): ListValue;

    public function hasDocuments(?Filter $filter = null): bool;

    public function hasNoDocuments(?Filter $filter = null): bool;

    public function hasDocument(string|ValueObject $identifier): bool;

    public function hasNoDocument(string|ValueObject $identifier): bool;

    /**
     * @param array<string>|ListValue<ValueObject|string|int> $identifiers
     */
    public function hasAllDocuments(array|ListValue $identifiers): bool;

    /**
     * @param T $state
     */
    public function upsertState(string|ValueObject $identifier, $state): void;

    public function deleteDoc(string|ValueObject $identifier): void;

    /**
     * @return class-string
     */
    public function stateClass(): string;
}
