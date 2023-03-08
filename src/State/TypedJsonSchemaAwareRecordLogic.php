<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\State;

use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use LogicException;

use function class_exists;
use function is_string;
use function preg_replace;

trait TypedJsonSchemaAwareRecordLogic
{
    use JsonSchemaAwareRecordLogic;

    /** @return class-string|null */
    public static function getTypeClassNameForState(): string|null
    {
        $stateClass = static::class;

        $typeClass = preg_replace('/(\w)+$/', 'Type', $stateClass);

        if (! (is_string($typeClass) && class_exists($typeClass))) {
            return null;
        }

        return $typeClass;
    }

    public static function __type(): string
    {
        $typeClassNameForState = static::getTypeClassNameForState();
        if (! is_string($typeClassNameForState)) {
            throw new LogicException('Unable to auto detect the type class for ' . static::class);
        }

        return $typeClassNameForState::typeRefName();
    }
}
