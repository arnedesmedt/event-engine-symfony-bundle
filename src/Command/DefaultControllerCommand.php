<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

use ADS\Bundle\EventEngineBundle\Exception\MessageException;
use ADS\JsonImmutableObjects\DefaultsAreNotRequired;

use function array_pop;
use function class_exists;
use function explode;
use function implode;
use function sprintf;

trait DefaultControllerCommand
{
    use DefaultsAreNotRequired;

    public static function __controller(): string
    {
        $parts = explode('\\', static::class);
        $name = array_pop($parts);
        array_pop($parts);
        $namespace = implode('\\', $parts);

        $controllerClass = sprintf('%s\\Controller\\%s', $namespace, $name);

        if (! class_exists($controllerClass)) {
            throw MessageException::noControllerFound(static::class);
        }

        return $controllerClass;
    }
}
