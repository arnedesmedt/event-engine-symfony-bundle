<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Util;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Util\StringUtil;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use ReflectionClass;
use RuntimeException;

use function sprintf;
use function str_replace;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;

final class EventEngineUtil
{
    /**
     * @param class-string $stateClass
     *
     * @return class-string
     */
    public static function fromStateToAggregateClass(string $stateClass): string
    {
        $aggregateRootName = self::fromStateToAggregateRootName($stateClass);
        /** @var class-string $aggregateRootClass */
        $aggregateRootClass = str_replace('State', $aggregateRootName, $stateClass);

        $stateClassByReturnType = self::fromAggregateClassToStateClass($aggregateRootClass);

        if ($stateClassByReturnType !== $stateClass) {
            throw new RuntimeException(
                sprintf(
                    'The state classes found (by property (\'%s\') and by namespace (\'%s\')) ' .
                    'for aggregate root \'%s\' don\'t match.',
                    $stateClassByReturnType,
                    $stateClass,
                    $aggregateRootClass
                )
            );
        }

        return $aggregateRootClass;
    }

    /**
     * @param class-string $stateClass
     */
    public static function fromStateToAggregateRootName(string $stateClass): string
    {
        $namespace = substr($stateClass, 0, -(strlen('State') + 1));
        $pos = strrpos($namespace, '\\');

        return substr($namespace, $pos + 1);
    }

    /**
     * @param class-string $stateClass
     */
    public static function fromStateToRepositoryId(string $stateClass): string
    {
        $aggregateRootName = self::fromStateToAggregateRootName($stateClass);

        return sprintf('event_engine.repository.%s', strtolower($aggregateRootName));
    }

    /**
     * @param class-string $aggregateClass
     */
    public static function fromAggregateClassToStateClass(string $aggregateClass): string
    {
        return $aggregateClass::stateClass();
    }

    public static function fromAggregateNameToAggregateClass(string $aggregateName, string $entityNamespace): string
    {
        return sprintf('%s\\%2$s\\%2$s', $entityNamespace, StringUtil::camelize($aggregateName, '_', true));
    }

    /**
     * @param class-string $aggregateClass
     */
    public static function fromAggregateClassToAggregateName(string $aggregateClass): string
    {
        return (new ReflectionClass($aggregateClass))->getShortName();
    }

    public static function fromAggregateNameToStreamName(string $aggregateName): string
    {
        return sprintf('%s_stream', StringUtil::decamelize($aggregateName));
    }

    public static function fromAggregateNameToDocumentStoreName(string $aggregateName): string
    {
        return sprintf('%s_state', StringUtil::decamelize($aggregateName));
    }

    public static function fromAggregateNameToStateClass(string $aggregateName, string $entityNamespace): string
    {
        /** @var class-string $aggregateClass */
        $aggregateClass = self::fromAggregateNameToAggregateClass($aggregateName, $entityNamespace);

        return self::fromAggregateClassToStateClass($aggregateClass);
    }

    public static function fromAggregateNameToStatesClass(string $aggregateName, string $entityNamespace): string
    {
        return self::fromAggregateNameToStateClass($aggregateName, $entityNamespace) . 's';
    }

    /**
     * @param class-string<AggregateRoot<JsonSchemaAwareRecord>> $aggregateClass
     */
    public static function fromAggregateClassToStreamName(string $aggregateClass): string
    {
        return self::fromAggregateNameToStreamName(
            self::fromAggregateClassToAggregateName($aggregateClass)
        );
    }
}
