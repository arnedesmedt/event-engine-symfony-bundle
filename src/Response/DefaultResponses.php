<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Response;

use ADS\Bundle\EventEngineBundle\Exception\ResponseException;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;
use function method_exists;
use function preg_match;
use function reset;

trait DefaultResponses
{
    /** @inheritDoc */
    public static function __responseClassesPerStatusCode(): array
    {
        $reflectionClass = new ReflectionClass(static::class);
        $staticMethods = $reflectionClass->getMethods(ReflectionMethod::IS_STATIC);

        $responseMethods = array_filter(
            $staticMethods,
            static function (ReflectionMethod $reflectionMethod) {
                $methodName = $reflectionMethod->getShortName();

                return preg_match('/^__extraResponseClasses/', $methodName);
            },
        );

        $responses = [];

        foreach ($responseMethods as $responseMethod) {
            $responses += $responseMethod->invoke(null);
        }

        return $responses;
    }

    public static function __responseClassForStatusCode(int $statusCode): string
    {
        $responses = self::__responseClassesPerStatusCode();

        if (! isset($responses[$statusCode])) {
            throw ResponseException::statusCodeNotFound($statusCode, static::class);
        }

        return $responses[$statusCode];
    }

    public static function __defaultResponseClass(): string
    {
        $statusCode = self::__defaultStatusCode();
        $responses = self::__responseClassesPerStatusCode();

        if ($statusCode === null) {
            return reset($responses);
        }

        if (! isset($responses[$statusCode])) {
            throw ResponseException::statusCodeNotFound($statusCode, static::class);
        }

        return $responses[$statusCode];
    }

    public static function __defaultStatusCode(): int|null
    {
        if (method_exists(static::class, '__httpMethod')) {
            $httpMethod = static::__httpMethod();

            return match ($httpMethod) {
                Request::METHOD_POST => Response::HTTP_CREATED,
                Request::METHOD_DELETE => Response::HTTP_NO_CONTENT,
                default => Response::HTTP_OK,
            };
        }

        return null;
    }
}
