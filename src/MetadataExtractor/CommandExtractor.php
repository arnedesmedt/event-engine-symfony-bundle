<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor as PreProcessorAttribute;
use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

use function array_filter;
use function array_map;
use function reset;
use function sprintf;

class CommandExtractor
{
    use ClassOrAttributeExtractor;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>
     */
    public function fromPreProcessorReflectionClass(ReflectionClass $reflectionClass): array
    {
        // Check if we have a pre processor service.
        $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            PreProcessor::class,
            PreProcessorAttribute::class,
        );

        $invokeMethod = $reflectionClass->getMethod('__invoke');
        $invokeParameters = $invokeMethod->getParameters();

        $firstParameter = reset($invokeParameters);

        if (! $firstParameter) {
            throw new RuntimeException(
                sprintf(
                    '__invoke method of PreProcessor \'%s\' has no parameters.',
                    $reflectionClass->getName(),
                ),
            );
        }

        /** @var ReflectionNamedType|ReflectionUnionType|null $commandType */
        $commandType = $firstParameter->getType();
        $commandTypes = $commandType instanceof ReflectionUnionType
            ? $commandType->getTypes()
            : [$commandType];

        $commandClasses = array_filter(
            array_map(
                static function (ReflectionNamedType|null $commandType) {
                    /** @var class-string<JsonSchemaAwareRecord> $commandClass */
                    $commandClass = $commandType?->getName();

                    return $commandClass;
                },
                $commandTypes,
            ),
        );

        if (empty($commandClasses)) {
            throw new RuntimeException(
                sprintf(
                    'PreProcessor \'%s\' has no linked commands.',
                    $reflectionClass->getName(),
                ),
            );
        }

        return $commandClasses;
    }
}
