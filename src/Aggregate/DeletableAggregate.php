<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Aggregate;

trait DeletableAggregate
{
    private bool $deleted = false;

    public function deleted() : bool
    {
        return $this->deleted === true;
    }
}
