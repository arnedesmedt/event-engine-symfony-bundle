<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Type;

use ADS\Bundle\EventEngineBundle\Type\Type;
use TeamBlue\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

class TestInterfaceType implements Type
{
    use JsonSchemaAwareRecordLogic;

    private string $test;
}
