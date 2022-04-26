<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

use ADS\Bundle\EventEngineBundle\Exception\MessageException;
use ADS\Bundle\EventEngineBundle\Exception\ResponseException;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\Schema\TypeSchema;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;
use function class_exists;
use function method_exists;
use function preg_match;
use function reset;
use function str_replace;
use function substr_count;

trait DefaultQuery
{
    use JsonSchemaAwareRecordLogic;

    public static function __resolver(): string
    {
        if (substr_count(static::class, '\\Query\\') > 1) {
            throw MessageException::nestedMessageFolder(static::class, 'Query');
        }

        $resolverClass = str_replace('\\Query\\', '\\Resolver\\', static::class);

        if (! class_exists($resolverClass)) {
            throw MessageException::noHandlerFound(static::class, 'resolver');
        }

        return $resolverClass;
    }

    public static function __responseSchemaForStatusCode(int $statusCode): TypeSchema
    {
        $responses = self::__responseSchemasPerStatusCode();

        if (! isset($responses[$statusCode])) {
            throw ResponseException::statusCodeNotFound($statusCode, static::class);
        }

        return $responses[$statusCode];
    }

    public static function __defaultStatusCode(): ?int
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

    public static function __defaultResponseSchema(): TypeSchema
    {
        $statusCode = self::__defaultStatusCode();
        $responses = self::__responseSchemasPerStatusCode();

        if ($statusCode === null) {
            $response = reset($responses);

            if ($response === false) {
                throw ResponseException::noResponsesFound(static::class);
            }

            return $response;
        }

        if (! isset($responses[$statusCode])) {
            throw ResponseException::statusCodeNotFound($statusCode, static::class);
        }

        return $responses[$statusCode];
    }

    /**
     * @inheritDoc
     */
    public static function __responseSchemasPerStatusCode(): array
    {
        $reflectionClass = new ReflectionClass(static::class);
        $staticMethods = $reflectionClass->getMethods(ReflectionMethod::IS_STATIC);

        $responseMethods = array_filter(
            $staticMethods,
            static function (ReflectionMethod $reflectionMethod) {
                $methodName = $reflectionMethod->getShortName();

                return preg_match('/^__extraResponse/', $methodName);
            }
        );

        $responses = [];

        foreach ($responseMethods as $responseMethod) {
            $responses += $responseMethod->invoke(null);
        }

        return $responses;
    }

    /**
     * @return array<string, TypeSchema>
     */
    public static function __extraResponse(): array
    {
        return [];
    }
}
