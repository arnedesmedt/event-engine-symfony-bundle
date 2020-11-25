<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Event;

interface Event
{
    public function __applyMethod(): string;
}
