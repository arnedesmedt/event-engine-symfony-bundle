<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\State;

use ADS\ValueObjects\Implementation\ListValue\IterableListValue;
use ADS\ValueObjects\Implementation\ListValue\JsonSchemaAwareCollectionLogic;

/** @extends IterableListValue<TestState> */
class TestStates extends IterableListValue
{
    use JsonSchemaAwareCollectionLogic;

    public static function itemType(): string
    {
        return TestState::class;
    }
}
