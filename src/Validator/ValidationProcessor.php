<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Validator;

use ADS\Bundle\EventEngineBundle\Message\Message;

interface ValidationProcessor
{
    public function __invoke(Message $message): void;
}
