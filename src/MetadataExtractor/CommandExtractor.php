<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Command as CommandAttribute;
use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor as PreProcessorAttribute;
use ADS\Bundle\EventEngineBundle\Command\Command;
use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use ADS\Util\MetadataExtractor\MetadataExtractor;
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
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function isCommandFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        return $this->metadataExtractor->hasAttributeOrClassFromReflectionClass(
            $reflectionClass,
            [
                CommandAttribute::class,
                Command::class,
            ],
        );
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return array<class-string<JsonSchemaAwareRecord>>
     */
    public function fromPreProcessorReflectionClass(ReflectionClass $reflectionClass): array
    {
        // Check if we have a pre processor service.
        $this->metadataExtractor->needAttributeOrClassFromReflectionClass(
            $reflectionClass,
            [
                PreProcessor::class,
                PreProcessorAttribute::class,
            ],
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
        /** @var array<ReflectionNamedType|null> $commandTypes */
        $commandTypes = $commandType instanceof ReflectionUnionType
            ? $commandType->getTypes()
            : [$commandType];

        /** @var array<class-string<JsonSchemaAwareRecord>> $commandClasses */
        $commandClasses = array_filter(
            array_map(
                static fn (ReflectionNamedType|null $commandType) => $commandType?->getName(),
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
