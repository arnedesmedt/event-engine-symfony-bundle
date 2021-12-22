<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

interface Query extends ReadWritePropertiesExtension
{
    /**
     * @return class-string|string
     */
    public static function __resolver(): string;
}
