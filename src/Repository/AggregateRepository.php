<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use ADS\ValueObjects\ValueObject;
use Throwable;

/**
 * @template T
 * @template TAgg
 * @extends StateRepository<T>
 */
interface AggregateRepository extends StateRepository
{
    /**
     * @param array<string, mixed>|null $document
     *
     * @return TAgg|null
     */
    public function aggregateFromDocument(?array $document);

    /**
     * @return TAgg|null
     */
    public function findAggregate(string|ValueObject $identifier);

    /**
     * @return TAgg
     */
    public function needAggregate(
        string|ValueObject $identifier,
        ?Throwable $exception = null
    );
}
