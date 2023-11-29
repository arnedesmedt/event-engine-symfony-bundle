<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AttributeExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ClassExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ControllerExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\InstanceExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\JsonSchemaExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\MetadataExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Controller\TestController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ControllerExtractorTest extends TestCase
{
    private ControllerExtractor $controllerExtractor;

    protected function setUp(): void
    {
        $this->controllerExtractor = new ControllerExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
                new InstanceExtractor(),
            ),
        );
    }

    public function testInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceControllerCommand::class);

        $controller = $this->controllerExtractor->fromReflectionClass($reflectionClass);

        $this->assertEquals(TestController::class, $controller);
    }

    public function testAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeControllerCommand::class);

        $controller = $this->controllerExtractor->fromReflectionClass($reflectionClass);

        $this->assertEquals(TestController::class, $controller);
    }

    public function testNonControllerCommandExtractor(): void
    {
        $reflectionClass = new ReflectionClass(JsonSchemaExtractor::class);

        $this->expectExceptionMessage('No metadata found');

        $this->controllerExtractor->fromReflectionClass($reflectionClass);
    }
}
