<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Type;

use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\Type\ArrayType;
use EventEngine\JsonSchema\Type\ObjectType;
use EventEngine\JsonSchema\Type\TypeRef;

abstract class DefaultType
{
    public static function getAll(): ArrayType
    {
        return JsonSchema::array(
            static::byId()
        );
    }

    public static function byId(): TypeRef
    {
        return JsonSchema::typeRef(static::typeRefName());
    }

    public static function emptyResponse(): ObjectType
    {
        return JsonSchema::object([]);
    }

    public static function created(): ObjectType
    {
        return JsonSchema::object([])
            ->describedAs('Created');
    }

    public static function ok(): ObjectType
    {
        return JsonSchema::object([])
            ->describedAs('OK');
    }

    abstract public static function typeRefName(): string;
}
