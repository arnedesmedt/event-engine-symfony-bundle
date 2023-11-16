<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Object\Aggregate;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Aggregate\EventSourced;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestAttributeEvent;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestInterfaceEvent;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestState;

/** @implements AggregateRoot<TestState> */
class TestAggregate implements AggregateRoot
{
    /** @use EventSourced<TestState> */
    use EventSourced;

    public static function stateClass(): string
    {
        return TestState::class;
    }

    public static function aggregateIdPropertyName(): string
    {
        return 'test';
    }

    public static function attributeCommand(TestAttributeAggregateCommand $testCommand): void
    {
    }

    private function whenTestAttributeEventAdded(TestAttributeEvent $event): void
    {
    }

    public function testInterfaceAggregateCommand(TestInterfaceAggregateCommand $testCommand): void
    {
    }

    private function whenTestInterfaceEvent(TestInterfaceEvent $event): void
    {
    }
}
