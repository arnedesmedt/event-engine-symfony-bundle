<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Response;

use ADS\Bundle\EventEngineBundle\Attribute\Response;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionAttribute;
use ReflectionClass;

use function reset;

class ResponseHelper
{
    /**
     * @param ReflectionClass<JsonSchemaAwareRecord> $reflectionClass
     *
     * @return class-string<JsonSchemaAwareRecord>|null
     */
    public function defaultResponseClassFromQueryReflectionClass(ReflectionClass $reflectionClass): string|null
    {
        $statusCode = null;
        $responseClassesPerStatusCode = [];

        if ($reflectionClass->implementsInterface(HasResponses::class)) {
            /** @var class-string<HasResponses> $message */
            $message = $reflectionClass->getName();

            $statusCode = $message::__defaultStatusCode();
            $responseClassesPerStatusCode = $message::__responseClassesPerStatusCode();
        }

        $responseAttributes = $reflectionClass->getAttributes(Response::class);

        if (! empty($responseAttributes)) {
            /** @var ReflectionAttribute<Response> $responseAttribute */
            $responseAttribute = reset($responseAttributes);
            $response = $responseAttribute->newInstance();

            $statusCode = $response->defaultStatusCode();
            $responseClassesPerStatusCode = $response->responseClassesPerStatusCode();
        }

        if (empty($responseClassesPerStatusCode)) {
            return null;
        }

        if ($statusCode === null) {
            return reset($responseClassesPerStatusCode);
        }

        return $responseClassesPerStatusCode[$statusCode] ?? reset($responseClassesPerStatusCode);
    }
}
