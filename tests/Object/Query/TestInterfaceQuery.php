<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Query;

use ADS\Bundle\EventEngineBundle\Query\DefaultQuery;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Bundle\EventEngineBundle\Tests\Object\Resolver\TestResolver;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\Ok;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

class TestInterfaceQuery implements Query
{
    use DefaultQuery;

    private string $test;

    public static function __resolver(): string
    {
        return TestResolver::class;
    }

    /** @return array<int, class-string<JsonSchemaAwareRecord>> */
    public static function __extraResponseClasses(): array
    {
        return [
            200 => Ok::class,
        ];
    }
}
