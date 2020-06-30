<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use Exception;

use function print_r;
use function sprintf;

final class JsonException extends Exception
{
    /**
     * @param array<mixed> $data
     */
    public static function couldNotEncode(array $data): self
    {
        return new static(
            sprintf(
                'Could not encode data \'%s\'.',
                print_r($data, true)
            )
        );
    }
}
