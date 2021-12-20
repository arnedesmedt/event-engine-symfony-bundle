<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\ValueObjects\ValueObject;
use Throwable;

interface AggregateRepository extends StateRepository
{
    /**
     * @param array<string, mixed>|null $document
     */
    public function aggregateFromDocument(?array $document): ?AggregateRoot;

    public function findAggregate(string|ValueObject $identifier): ?AggregateRoot;

    public function needAggregate(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    ): AggregateRoot;
}
