<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

/** @method static with(array $recordData) */
trait DeletableAggregate
{
    private bool $deleted = false;

    public function deleted(): bool
    {
        return $this->deleted === true;
    }

    public function delete(): static
    {
        $this->deleted = true;

        return $this;
    }
}
