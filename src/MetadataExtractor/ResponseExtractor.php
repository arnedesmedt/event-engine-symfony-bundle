<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Response;
use ADS\Bundle\EventEngineBundle\Response\HasResponses;
use ADS\Util\MetadataExtractor\MetadataExtractorAware;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;

class ResponseExtractor
{
    use MetadataExtractorAware;

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

    /** @param ReflectionClass<object> $reflectionClass **/
    public function defaultStatusCodeFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        /** @var int $defaultStatusCode */
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
     * @return class-string<JsonSchemaAwareRecord>
     */
    public function defaultResponseClassFromReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var class-string<JsonSchemaAwareRecord> $defaultResponseClass */
        $defaultResponseClass = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<HasResponses> $class */
                HasResponses::class => static fn (string $class) => $class::__defaultResponseClass(),
                Response::class => static fn (Response $response) => $response->defaultResponseClass(),
            ],
        );

        return $defaultResponseClass;
    }
}
