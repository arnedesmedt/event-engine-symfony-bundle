<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Resolver;

use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestInterfaceQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\Ok;

class TestResolver
{
    public function __invoke(TestInterfaceQuery|TestAttributeQuery $query): Ok
    {
        return Ok::fromArray(['id' => 'test']);
    }
}
