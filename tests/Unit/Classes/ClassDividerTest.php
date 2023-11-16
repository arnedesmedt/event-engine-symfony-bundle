<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\Classes;

use ADS\Bundle\EventEngineBundle\Classes\ClassDivider;
use ADS\Bundle\EventEngineBundle\Tests\Object\Aggregate\TestAggregate;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestAttributeEvent;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestInterfaceEvent;
use ADS\Bundle\EventEngineBundle\Tests\Object\Listener\TestAttributeListener;
use ADS\Bundle\EventEngineBundle\Tests\Object\Listener\TestInterfaceListener;
use ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor\TestAttributePreProcessor;
use ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor\TestInterfacePreProcessor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestAttributeProjector;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestInterfaceProjector;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestInterfaceQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Type\TestAttributeType;
use ADS\Bundle\EventEngineBundle\Tests\Object\Type\TestInterfaceType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** @SuppressWarnings(PHPMD.CouplingBetweenObjects) */
class ClassDividerTest extends TestCase
{
    private ClassDivider $classDivider;

    protected function setUp(): void
    {
        $this->classDivider = new ClassDivider([__DIR__ . '/../../Object/']);
    }

    public function testCommands(): void
    {
        $commands = $this->classDivider->commands();

        $this->assertCount(4, $commands);
        $this->assertArrayHasKey(TestAttributeAggregateCommand::class, $commands);
        $this->assertArrayHasKey(TestAttributeControllerCommand::class, $commands);
        $this->assertArrayHasKey(TestInterfaceAggregateCommand::class, $commands);
        $this->assertArrayHasKey(TestInterfaceControllerCommand::class, $commands);
    }

    public function testAggregateCommands(): void
    {
        $commands = $this->classDivider->aggregateCommands();

        $this->assertCount(2, $commands);
        $this->assertArrayHasKey(TestAttributeAggregateCommand::class, $commands);
        $this->assertArrayHasKey(TestInterfaceAggregateCommand::class, $commands);
    }

    public function testControllerCommands(): void
    {
        $commands = $this->classDivider->controllerCommands();

        $this->assertCount(2, $commands);
        $this->assertArrayHasKey(TestAttributeControllerCommand::class, $commands);
        $this->assertArrayHasKey(TestInterfaceControllerCommand::class, $commands);
    }

    public function testQueries(): void
    {
        $queries = $this->classDivider->queries();

        $this->assertCount(2, $queries);
        $this->assertArrayHasKey(TestAttributeQuery::class, $queries);
        $this->assertArrayHasKey(TestInterfaceQuery::class, $queries);
    }

    public function testEvents(): void
    {
        $events = $this->classDivider->events();

        $this->assertCount(2, $events);
        $this->assertArrayHasKey(TestAttributeEvent::class, $events);
        $this->assertArrayHasKey(TestInterfaceEvent::class, $events);
    }

    public function testAggregates(): void
    {
        $aggregates = $this->classDivider->aggregates();

        $this->assertCount(1, $aggregates);
        $this->assertArrayHasKey(TestAggregate::class, $aggregates);
    }

    public function testPreProcessors(): void
    {
        $preProcessors = $this->classDivider->preProcessors();

        $this->assertCount(2, $preProcessors);
        $this->assertArrayHasKey(TestAttributePreProcessor::class, $preProcessors);
        $this->assertArrayHasKey(TestInterfacePreProcessor::class, $preProcessors);
    }

    public function testListeners(): void
    {
        $listeners = $this->classDivider->listeners();

        $this->assertCount(2, $listeners);
        $this->assertArrayHasKey(TestAttributeListener::class, $listeners);
        $this->assertArrayHasKey(TestInterfaceListener::class, $listeners);
    }

    public function testProjectors(): void
    {
        $projectors = $this->classDivider->projectors();

        $this->assertCount(2, $projectors);
        $this->assertArrayHasKey(TestAttributeProjector::class, $projectors);
        $this->assertArrayHasKey(TestInterfaceProjector::class, $projectors);
    }

    public function testTypes(): void
    {
        $types = $this->classDivider->types();

        $this->assertCount(2, $types);
        $this->assertArrayHasKey(TestAttributeType::class, $types);
        $this->assertArrayHasKey(TestInterfaceType::class, $types);
    }

    public function testNoJsonSchemaAwareRecord(): void
    {
        $classContainer = new ClassDivider([__DIR__ . '/../../FailingObject/']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Class ADS\Bundle\EventEngineBundle\Tests\FailingObject\Command\TestAttributeAggregateCommand ' .
            'is not a JsonSchemaAwareRecord, but should be used as aggregate command',
        );
        $classContainer->commands();
    }
}
