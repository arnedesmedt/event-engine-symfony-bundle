<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\EventClassExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Listener\TestAttributeListener;
use ADS\Bundle\EventEngineBundle\Tests\Object\Listener\TestInterfaceListener;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestAttributeProjector;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestInterfaceProjector;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TeamBlue\JsonImmutableObjects\MetadataExtractor\JsonSchemaExtractor;
use TeamBlue\Util\MetadataExtractor\AttributeExtractor;
use TeamBlue\Util\MetadataExtractor\ClassExtractor;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

class EventClassExtractorTest extends TestCase
{
    private EventClassExtractor $eventClassExtractor;

    protected function setUp(): void
    {
        $this->eventClassExtractor = new EventClassExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
            ),
        );
    }

    public function testInterfaceListenerFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceListener::class);

        /** @var array<class-string> $eventClasses */
        $eventClasses = $this->eventClassExtractor->fromListenerReflectionClass($reflectionClass);

        $this->assertCount(1, $eventClasses);
    }

    public function testAttributeListenerFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeListener::class);

        /** @var array<class-string> $eventClasses */
        $eventClasses = $this->eventClassExtractor->fromListenerReflectionClass($reflectionClass);

        $this->assertCount(1, $eventClasses);
    }

    public function testInterfaceProjectorFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceProjector::class);

        $eventClasses = $this->eventClassExtractor->fromProjectorReflectionClass($reflectionClass);

        $this->assertCount(1, $eventClasses);
    }

    public function testAttributeProjectorFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeProjector::class);

        $eventClasses = $this->eventClassExtractor->fromProjectorReflectionClass($reflectionClass);

        $this->assertCount(1, $eventClasses);
    }

    public function testNonListenerExtractor(): void
    {
        $reflectionClass = new ReflectionClass(JsonSchemaExtractor::class);

        $this->expectExceptionMessage('No metadata found');

        $this->eventClassExtractor->fromListenerReflectionClass($reflectionClass);
    }
}
