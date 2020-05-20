<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use ADS\Bundle\EventEngineBundle\Exception\MessageException;
use function array_pop;
use function class_exists;
use function count;
use function explode;
use function implode;
use function sprintf;

trait DefaultControllerCommand
{
    public static function __controller() : string
    {
        $parts = explode('\\', static::class);
        array_pop($parts);
        array_pop($parts);
        $namespace = implode('\\', $parts);

        $controllerClass = sprintf('%s\\%s', $namespace, $parts[count($parts) - 1]);

        if (! class_exists($controllerClass)) {
            throw MessageException::noControllerFound(static::class);
        }

        return $controllerClass;
    }
}
