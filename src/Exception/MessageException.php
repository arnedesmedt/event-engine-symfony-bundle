<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use Exception;

use function sprintf;

final class MessageException extends Exception
{
    /** @param class-string $message */
    public static function noHandlerFound(string $message, string $type): self
    {
        return new self(
            sprintf(
                'Could not find a %s for message \'%s\'.',
                $type,
                $message,
            ),
        );
    }

    public static function nestedMessageFolder(string $class, string $folder): self
    {
        return new self(
            sprintf(
                'The message \'%s\' has a nested folder structure for the directory \'%s\'.',
                $class,
                $folder,
            ),
        );
    }
}
