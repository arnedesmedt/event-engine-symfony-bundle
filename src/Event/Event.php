<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Event;

use ADS\Bundle\EventEngineBundle\Message\Message;

interface Event extends Message
{
    public function __applyMethod(): string;
}
