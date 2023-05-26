<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Message;

use EventEngine\Messaging\Message;
use JetBrains\PhpStorm\Deprecated;

#[Deprecated]
abstract class MessageWrapper
{
    private function __construct(private Message $message)
    {
    }

    public static function fromMessage(Message $message): self
    {
        // @phpstan-ignore-next-line
        return new static($message);
    }

    public function message(): Message
    {
        return $this->message;
    }
}
