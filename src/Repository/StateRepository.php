<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\Implementation\ListValue\IterableListValue;
use ADS\ValueObjects\ListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\DocumentStore\Filter\Filter;
use EventEngine\DocumentStore\PartialSelect;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use Throwable;
use Traversable;

/**
 * @template TStates of IterableListValue
 * @template TState of JsonSchemaAwareRecord
 * @template TId of ValueObject
 */
interface StateRepository
{
    /**
     * @param string|TId $identifier
     *
     * @return array{state: array<string, mixed>}|null
     */
    public function findDocument($identifier): ?array;

    /**
     * @param string|TId $identifier
     *
     * @return array{state: array<string, mixed>}
     */
    public function needDocument(
        $identifier,
        ?Throwable $exception = null
    ): array;

    /**
     * @param string|TId $identifier
     */
    public function dontNeedDocument(
        $identifier,
        ?Throwable $exception = null
    ): void;

    /**
     * @param string|TId $identifier
     *
     * @return TState
     */
    public function needDocumentState(
        $identifier,
        ?Throwable $exception = null
    );

    /**
     * @return Traversable<array{state: array<string, mixed>}>
     */
    public function findDocuments(?Filter $filter = null, ?int $skip = null, ?int $limit = null): Traversable;

    /**
     * @param array<string|TId>|ListValue<TId> $identifiers
     *
     * @return Traversable<array{state: array<string, mixed>}>
     */
    public function findDocumentsByIds(array|ListValue $identifiers): Traversable;

    /**
     * @param array<string|TId>|ListValue<TId> $identifiers
     *
     * @return array<array{state: array<string, mixed>}>
     */
    public function needDocumentsByIds(array|ListValue $identifiers): array;

    /**
     * @param array<string|TId>|ListValue<TId> $identifiers
     *
     * @return TStates
     */
    public function findDocumentStatesByIds(array|ListValue $identifiers);

    /**
     * @param array<string|TId>|ListValue<TId> $identifiers
     *
     * @return TStates
     */
    public function needDocumentStatesByIds(array|ListValue $identifiers);

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
     * @param string|TId $identifier
     *
     * @return TState|null
     */
    public function findDocumentState($identifier);

    /**
     * @return TStates
     */
    public function findDocumentStates(?Filter $filter = null, ?int $skip = null, ?int $limit = null);

    /**
     * @return ListValue<TId>
     */
    public function findDocumentIdValueObjects(?Filter $filter = null): ListValue;

    public function hasDocuments(?Filter $filter = null): bool;

    public function hasNoDocuments(?Filter $filter = null): bool;

    /**
     * @param string|TId $identifier
     */
    public function hasDocument($identifier): bool;

    /**
     * @param string|TId $identifier
     */
    public function hasNoDocument($identifier): bool;

    /**
     * @param array<string|TId>|ListValue<TId> $identifiers
     */
    public function hasAllDocuments(array|ListValue $identifiers): bool;

    /**
     * @param string|TId $identifier
     * @param TState $state
     */
    public function upsertState($identifier, $state): void;

    /**
     * @param string|TId $identifier
     */
    public function deleteDoc($identifier): void;

    /**
     * @return class-string<TState>
     */
    public function stateClass(): string;
}
