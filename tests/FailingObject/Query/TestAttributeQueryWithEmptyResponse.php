<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\FailingObject\Query;

use ADS\Bundle\EventEngineBundle\Attribute\Query;
use ADS\Bundle\EventEngineBundle\Tests\Object\Resolver\TestResolver;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Query(
    resolver: TestResolver::class,
)]
class TestAttributeQueryWithEmptyResponse implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
