<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Response;
use ADS\Bundle\EventEngineBundle\Response\HasResponses;
use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use RuntimeException;

use function reset;
use function sprintf;

class ResponseExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function hasResponsesFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        return $this->metadataExtractor->hasAttributeOrClassFromReflectionClass(
            $reflectionClass,
            [
                HasResponses::class,
                Response::class,
            ],
        );
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<int, class-string<JsonSchemaAwareRecord>>
     */
    public function responseClassesPerStatusCodeFromReflectionClass(ReflectionClass $reflectionClass): array
    {
        /** @var array<int, class-string<JsonSchemaAwareRecord>> $responseClassesPerStatusCode */
        $responseClassesPerStatusCode = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<HasResponses> $class */
                HasResponses::class => static fn (string $class) => $class::__responseClassesPerStatusCode(),
                Response::class => static fn (Response $response) => $response->responseClassesPerStatusCode(),
            ],
        );

        return $responseClassesPerStatusCode;
    }

    /** @param ReflectionClass<object> $reflectionClass **/
    public function defaultStatusCodeFromReflectionClass(ReflectionClass $reflectionClass): int|null
    {
        /** @var int|null $defaultStatusCode */
        $defaultStatusCode = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<HasResponses> $class */
                HasResponses::class => static fn (string $class) => $class::__defaultStatusCode(),
                Response::class => static fn (Response $response) => $response->defaultStatusCode(),
            ],
        );

        return $defaultStatusCode;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return class-string<JsonSchemaAwareRecord|JsonSchemaAwareCollection>
     */
    public function defaultResponseClassFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        $responseClassesPerStatusCode = $this->responseClassesPerStatusCodeFromReflectionClass($reflectionClass);
        $defaultStatusCode = $this->defaultStatusCodeFromReflectionClass($reflectionClass);

        if (isset($responseClassesPerStatusCode[$defaultStatusCode])) {
            return $responseClassesPerStatusCode[$defaultStatusCode];
        }

        if (! empty($responseClassesPerStatusCode)) {
            return reset($responseClassesPerStatusCode);
        }

        throw new RuntimeException(
            sprintf(
                'No default response class found for message \'%s\'.',
                $reflectionClass->getName(),
            ),
        );
    }
}
