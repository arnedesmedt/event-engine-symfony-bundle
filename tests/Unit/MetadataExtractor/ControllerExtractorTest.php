<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\ControllerCommand as ControllerCommandAttribute;
use ADS\Bundle\EventEngineBundle\Command\ControllerCommand;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ControllerExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\JsonSchemaExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Controller\TestController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function sprintf;

class ControllerExtractorTest extends TestCase
{
    private ControllerExtractor $controllerExtractor;

    protected function setUp(): void
    {
        $this->controllerExtractor = new ControllerExtractor();
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

        $this->expectExceptionMessage(
            sprintf(
                'No implementation of \'%s\' found or attribute \'%s\' added for \'%s\'.',
                ControllerCommand::class,
                ControllerCommandAttribute::class,
                $reflectionClass->getName(),
            ),
        );

        $this->controllerExtractor->fromReflectionClass($reflectionClass);
    }
}
