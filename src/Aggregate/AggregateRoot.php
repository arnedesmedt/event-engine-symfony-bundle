<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

/** @template TState of JsonSchemaAwareRecord */
interface AggregateRoot
{
    /** @return class-string<JsonSchemaAwareRecord> */
    public static function stateClass(): string;

    public static function reconstituteFromHistory(Event ...$domainEvents): static;

    /** @param array<string, mixed> $state */
    public static function reconstituteFromStateArray(array $state): static;

    /** @return array<Event> */
    public function popRecordedEvents(): array;

    public function apply(JsonSchemaAwareRecord $event): void;

    /** @return array<string, mixed> */
    public function toArray(): array;

    /** @return TState */
    public function state();

    public static function aggregateIdPropertyName(): string;

    public static function createForSeed(): static;
}
