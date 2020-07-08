<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

/**
 * @method static with(array $recordData)
 */
trait DeletableAggregate
{
    private bool $deleted = false;

    public function deleted(): bool
    {
        return $this->deleted === true;
    }

    /**
     * @return static
     */
    public function delete()
    {
        $this->deleted = true;

        return $this;
    }
}
