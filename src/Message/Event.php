<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

interface Event
{
    public function __applyMethod() : string;
}
