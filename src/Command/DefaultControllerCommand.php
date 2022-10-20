<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

use ADS\Bundle\EventEngineBundle\Exception\MessageException;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

use function class_exists;
use function str_replace;
use function substr_count;

trait DefaultControllerCommand
{
    use JsonSchemaAwareRecordLogic;
    use OAuthRoleAuthorization;

    public static function __controller(): string
    {
        if (substr_count(static::class, '\\Command\\') > 1) {
            throw MessageException::nestedMessageFolder(static::class, 'Command');
        }

        $controllerClass = str_replace('\\Command\\', '\\Controller\\', static::class);

        if (! class_exists($controllerClass)) {
            throw MessageException::noHandlerFound(static::class, 'controller');
        }

        return $controllerClass;
    }
}
