<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Repository;

use ADS\Bundle\EventEngineBundle\Repository\Repository;
use ADS\Bundle\EventEngineBundle\Tests\Object\Aggregate\TestAggregate;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestState;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestStates;
use TeamBlue\ValueObjects\Implementation\String\StringValue;

/** @extends Repository<TestAggregate, TestStates, TestState, StringValue> */
class TestAggregateRepository extends Repository
{
}
