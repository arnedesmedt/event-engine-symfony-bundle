<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Query;

use ADS\Bundle\EventEngineBundle\Attribute\Query;
use ADS\Bundle\EventEngineBundle\Tests\Object\Resolver\TestResolver;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\NotFound;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\Ok;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Query(
    resolver: TestResolver::class,
    responseClassesPerStatusCode: [
        200 => Ok::class,
        404 => NotFound::class,
    ],
    defaultStatusCode: 200,
)]
class TestAttributeQuery implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
