<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Type;

use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\Type;
use EventEngine\JsonSchema\Type\ArrayType;
use EventEngine\JsonSchema\Type\ObjectType;
use EventEngine\Schema\TypeSchema;

abstract class DefaultType
{
    public static function getAll(): ArrayType
    {
        /** @var Type $byId */
        $byId = static::byId();

        return JsonSchema::array($byId);
    }

    public static function byId(): TypeSchema
    {
        return JsonSchema::typeRef(static::typeRefName());
    }

    public static function emptyResponse(): ObjectType
    {
        return JsonSchema::object([]);
    }

    public static function created(): ObjectType
    {
        return self::emptyResponse()->describedAs('Created');
    }

    public static function deleted(): ObjectType
    {
        return self::emptyResponse()->describedAs('Deleted');
    }

    public static function ok(): ObjectType
    {
        return self::emptyResponse()->describedAs('OK');
    }

    abstract public static function typeRefName(): string;
}
