<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\State;

use TeamBlue\ValueObjects\Implementation\ListValue\IterableListValue;
use TeamBlue\ValueObjects\Implementation\ListValue\JsonSchemaAwareCollectionLogic;

/** @extends IterableListValue<TestState> */
class TestStates extends IterableListValue
{
    use JsonSchemaAwareCollectionLogic;

    public static function itemType(): string
    {
        return TestState::class;
    }
}
