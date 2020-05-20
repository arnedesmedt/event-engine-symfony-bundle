<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use Exception;
use function sprintf;

final class MessageException extends Exception
{
    /**
     * @param class-string $message
     */
    public static function noControllerFound(string $message) : self
    {
        return new static(
            sprintf(
                'Could not find a controller for message \'%s\'.',
                $message
            )
        );
    }
}
