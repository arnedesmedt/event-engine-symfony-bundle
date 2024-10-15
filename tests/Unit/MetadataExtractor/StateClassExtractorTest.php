<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\StateClassExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Aggregate\TestAggregate;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestAttributeProjector;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestInterfaceProjector;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestState;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TeamBlue\Util\MetadataExtractor\AttributeExtractor;
use TeamBlue\Util\MetadataExtractor\ClassExtractor;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

class StateClassExtractorTest extends TestCase
{
    private StateClassExtractor $stateClassExtractor;

    protected function setUp(): void
    {
        $this->stateClassExtractor = new StateClassExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
            ),
        );
    }

    public function testAggregateRootFromReflectionClass(): void
    {
        /** @var ReflectionClass<AggregateRoot<JsonSchemaAwareRecord>> $reflectionClass */
        $reflectionClass = new ReflectionClass(TestAggregate::class);

        $stateClass = $this->stateClassExtractor->fromAggregateRootReflectionClass($reflectionClass);

        $this->assertEquals(TestState::class, $stateClass);
    }

    public function testProjectorInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceProjector::class);

        $stateClass = $this->stateClassExtractor->fromProjectorReflectionClass($reflectionClass);

        $this->assertEquals(TestState::class, $stateClass);
    }

    public function testProjectAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeProjector::class);

        $stateClass = $this->stateClassExtractor->fromProjectorReflectionClass($reflectionClass);

        $this->assertEquals(TestState::class, $stateClass);
    }

    public function testForNonAggregateRootFromReflectionClass(): void
    {
        /** @var ReflectionClass<AggregateRoot<JsonSchemaAwareRecord>> $reflectionClass */
        $reflectionClass = new ReflectionClass(TestState::class);

        $this->expectExceptionMessage('No metadata found');

        $this->stateClassExtractor->fromAggregateRootReflectionClass($reflectionClass);
    }
}
