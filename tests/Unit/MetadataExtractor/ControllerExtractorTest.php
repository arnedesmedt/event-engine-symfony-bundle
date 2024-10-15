<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\ControllerExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Controller\TestController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TeamBlue\JsonImmutableObjects\MetadataExtractor\JsonSchemaExtractor;
use TeamBlue\Util\MetadataExtractor\AttributeExtractor;
use TeamBlue\Util\MetadataExtractor\ClassExtractor;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

class ControllerExtractorTest extends TestCase
{
    private ControllerExtractor $controllerExtractor;

    protected function setUp(): void
    {
        $this->controllerExtractor = new ControllerExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
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
