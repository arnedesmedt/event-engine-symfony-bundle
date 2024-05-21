<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Response;

use ADS\Bundle\EventEngineBundle\Response\Implementation\Created;
use ADS\Bundle\EventEngineBundle\Response\Implementation\Deleted;
use ADS\Bundle\EventEngineBundle\Response\Implementation\Ok;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function method_exists;

trait DefaultResponses
{
    public static function __defaultStatusCode(): int
    {
        if (method_exists(static::class, '__httpMethod')) {
            $httpMethod = static::__httpMethod();

            return match ($httpMethod) {
                Request::METHOD_POST => Response::HTTP_CREATED,
                Request::METHOD_DELETE => Response::HTTP_NO_CONTENT,
                default => Response::HTTP_OK,
            };
        }

        return Response::HTTP_OK;
    }

    /** @return class-string<JsonSchemaAwareRecord> */
    public static function __defaultResponseClass(): string
    {
        if (method_exists(static::class, '__httpMethod')) {
            $httpMethod = static::__httpMethod();

            return match ($httpMethod) {
                Request::METHOD_POST => Created::class,
                Request::METHOD_DELETE => Deleted::class,
                default => Ok::class,
            };
        }

        return Ok::class;
    }
}
