<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Query;

use ADS\Bundle\EventEngineBundle\Attribute\Query;
use ADS\Bundle\EventEngineBundle\Tests\Object\Resolver\TestResolver;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\Ok;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use Symfony\Component\HttpFoundation\Response;
use TeamBlue\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

#[Query(
    resolver: TestResolver::class,
    defaultResponseClass: Ok::class,
    defaultStatusCode: Response::HTTP_OK,
)]
class TestAttributeQuery implements JsonSchemaAwareRecord
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
