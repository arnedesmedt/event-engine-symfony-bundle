<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AggregateCommandExtractorTest extends TestCase
{
    private AggregateCommandExtractor $aggregateCommandExtractor;

    protected function setUp(): void
    {
        $this->aggregateCommandExtractor = new AggregateCommandExtractor();
    }

    public function testAggregateMethodInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $method = $this->aggregateCommandExtractor->aggregateMethodFromReflectionClass($reflectionClass);

        $this->assertEquals('TestInterfaceAggregateCommand', $method);
    }

    public function testAggregateMethodAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $method = $this->aggregateCommandExtractor->aggregateMethodFromReflectionClass($reflectionClass);

        $this->assertEquals('attributeCommand', $method);
    }

    public function testNewInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $new = $this->aggregateCommandExtractor->newFromReflectionClass($reflectionClass);

        $this->assertFalse($new);
    }

    public function testNewAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $new = $this->aggregateCommandExtractor->newFromReflectionClass($reflectionClass);

        $this->assertTrue($new);
    }
}