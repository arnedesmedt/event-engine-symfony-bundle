<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Query;
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
    use ClassOrAttributeExtractor;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<int, class-string<JsonSchemaAwareRecord>>
     */
    public function responseClassesPerStatusCodeFromReflectionClass(ReflectionClass $reflectionClass): array
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            HasResponses::class,
            [Query::class, Response::class],
        );

        return $classOrAttributeInstance instanceof Response
            ? $classOrAttributeInstance->responseClassesPerStatusCode()
            : $classOrAttributeInstance::__responseClassesPerStatusCode();
    }

    /** @param ReflectionClass<object> $reflectionClass **/
    public function defaultStatusCodeFromReflectionClass(ReflectionClass $reflectionClass): int|null
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            HasResponses::class,
            [Query::class, Response::class],
        );

        return $classOrAttributeInstance instanceof Response
            ? $classOrAttributeInstance->defaultStatusCode()
            : $classOrAttributeInstance::__defaultStatusCode();
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
