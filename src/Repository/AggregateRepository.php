<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\ValueObjects\ValueObject;
use Throwable;

interface AggregateRepository extends StateRepository
{
    /**
     * @param array<mixed>|null $document
     */
    public function aggregateFromDocument(?array $document): ?AggregateRoot;

    /**
     * @param string|ValueObject $identifier
     */
    public function findAggregate($identifier): ?AggregateRoot;

    /**
     * @param string|ValueObject $identifier
     */
    public function needAggregate(
        $identifier,
        ?Throwable $exception = null
    ): AggregateRoot;
}
