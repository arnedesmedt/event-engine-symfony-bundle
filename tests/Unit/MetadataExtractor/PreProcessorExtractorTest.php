<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AttributeExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ClassExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\InstanceExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\MetadataExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\PreProcessorExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor\TestAttributePreProcessor;
use ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor\TestInterfacePreProcessor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PreProcessorExtractorTest extends TestCase
{
    private PreProcessorExtractor $preProcessorExtractor;

    protected function setUp(): void
    {
        $this->preProcessorExtractor = new PreProcessorExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
                new InstanceExtractor(),
            ),
        );
    }

    public function testPriorityInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfacePreProcessor::class);

        $priority = $this->preProcessorExtractor->priorityFromReflectionClass($reflectionClass);

        $this->assertEquals(0, $priority);
    }

    public function testPriorityAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributePreProcessor::class);

        $priority = $this->preProcessorExtractor->priorityFromReflectionClass($reflectionClass);

        $this->assertEquals(5, $priority);
    }
}
