<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use RuntimeException;

use function sprintf;

final class ResponseException
{
    /**
     * @param class-string $class
     */
    public static function statusCodeNotFound(int $statusCode, string $class): RuntimeException
    {
        return new RuntimeException(
            sprintf(
                'Could not found a response with status code \'%d\' for message \'%s\'.',
                $statusCode,
                $class
            )
        );
    }

    /**
     * @param class-string $class
     */
    public static function noResponsesFound(string $class): RuntimeException
    {
        return new RuntimeException(
            sprintf(
                'No responses found for message \'%s\'.',
                $class
            )
        );
    }
}
