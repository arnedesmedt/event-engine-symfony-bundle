<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use Exception;

use function sprintf;

final class ResponseException extends Exception
{
    /**
     * @param class-string $class
     */
    public static function statusCodeNotFound(int $statusCode, string $class): self
    {
        return new static(
            sprintf(
                'Could not found a response with status code \'%d\' for message \'%s\'.',
                $statusCode,
                $class
            )
        );
    }
}
