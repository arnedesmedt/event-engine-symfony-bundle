<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Query;

use ADS\Bundle\EventEngineBundle\Query\DefaultQuery;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Bundle\EventEngineBundle\Tests\Object\Resolver\TestResolver;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\Ok;
use Symfony\Component\HttpFoundation\Response;

class TestInterfaceQuery implements Query
{
    use DefaultQuery;

    private string $test;

    public static function __resolver(): string
    {
        return TestResolver::class;
    }

    public static function __defaultStatusCode(): int
    {
        return Response::HTTP_OK;
    }

    public static function __defaultResponseClass(): string
    {
        return Ok::class;
    }
}
