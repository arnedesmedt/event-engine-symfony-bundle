<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

/**
 * @template TState of JsonSchemaAwareRecord
 */
interface AggregateRoot
{
    /**
     * @return class-string
     */
    public static function stateClass(): string;

    /**
     * @return static
     */
    public static function reconstituteFromHistory(Event ...$domainEvents): static;

    /**
     * @param array<string, mixed> $state
     *
     * @return static
     */
    public static function reconstituteFromStateArray(array $state): static;

    /**
     * @return array<Event>
     */
    public function popRecordedEvents(): array;

    public function apply(Event $event): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @return TState
     */
    public function state();

    public static function aggregateId(): string;

    public static function createForSeed(): static;
}
