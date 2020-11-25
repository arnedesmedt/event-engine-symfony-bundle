<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

use ADS\Bundle\EventEngineBundle\Event\Event;
use EventEngine\Data\ImmutableRecord;

interface AggregateRoot
{
    public static function reconstituteFromHistory(Event ...$domainEvents): AggregateRoot;

    /**
     * @param array<mixed> $state
     */
    public static function reconstituteFromStateArray(array $state): AggregateRoot;

    /**
     * @return array<Event>
     */
    public function popRecordedEvents(): array;

    public function apply(Event $event): void;

    /**
     * @return array<mixed>
     */
    public function toArray(): array;

    /**
     * @return ImmutableRecord
     */
    public function state(); // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
}
