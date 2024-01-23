<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Util\MetadataExtractor\AttributeExtractor;
use ADS\Util\MetadataExtractor\ClassExtractor;
use ADS\Util\MetadataExtractor\MetadataExtractor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AggregateCommandExtractorTest extends TestCase
{
    private AggregateCommandExtractor $aggregateCommandExtractor;

    protected function setUp(): void
    {
        $this->aggregateCommandExtractor = new AggregateCommandExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
            ),
        );
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

    public function testAggregateIdInterface(): void
    {
        $aggregateId = $this->aggregateCommandExtractor->aggregateIdFromAggregateCommand(
            TestInterfaceAggregateCommand::fromArray(['test' => 'test']),
        );

        $this->assertEquals('test', $aggregateId);
    }

    public function testAggregateIdAttribute(): void
    {
        $aggregateId = $this->aggregateCommandExtractor->aggregateIdFromAggregateCommand(
            TestAttributeAggregateCommand::fromArray(['test' => 'test']),
        );

        $this->assertEquals('test', $aggregateId);
    }

    public function testAggregateIdNotFound(): void
    {
        $aggregateId = $this->aggregateCommandExtractor->aggregateIdFromAggregateCommand(
            TestAttributeQuery::fromArray(['test' => 'test']),
        );

        $this->assertNull($aggregateId);
    }
}
