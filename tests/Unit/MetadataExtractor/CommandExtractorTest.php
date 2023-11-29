<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AttributeExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ClassExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\CommandExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\InstanceExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\MetadataExtractor;
use ADS\Bundle\EventEngineBundle\Tests\FailingObject\PreProcessor\TestPreProcessorWithoutCommands;
use ADS\Bundle\EventEngineBundle\Tests\FailingObject\PreProcessor\TestPreProcessorWithoutType;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor\TestAttributePreProcessor;
use ADS\Bundle\EventEngineBundle\Tests\Object\PreProcessor\TestInterfacePreProcessor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function sprintf;

class CommandExtractorTest extends TestCase
{
    private CommandExtractor $commandExtractor;

    protected function setUp(): void
    {
        $this->commandExtractor = new CommandExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
                new InstanceExtractor(),
            ),
        );
    }

    public function testPreProcessorInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfacePreProcessor::class);

        $commands = $this->commandExtractor->fromPreProcessorReflectionClass($reflectionClass);

        $this->assertCount(1, $commands);
        $this->assertEquals(TestInterfaceAggregateCommand::class, $commands[0]);
    }

    public function testPreProcessorAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributePreProcessor::class);

        $commands = $this->commandExtractor->fromPreProcessorReflectionClass($reflectionClass);

        $this->assertCount(1, $commands);
        $this->assertEquals(TestAttributeControllerCommand::class, $commands[0]);
    }

    public function testPreProcessorWithoutCommands(): void
    {
        $reflectionClass = new ReflectionClass(TestPreProcessorWithoutCommands::class);

        $this->expectExceptionMessage(sprintf(
            '__invoke method of PreProcessor \'%s\' has no parameters.',
            TestPreProcessorWithoutCommands::class,
        ));

        $this->commandExtractor->fromPreProcessorReflectionClass($reflectionClass);
    }

    public function testPreProcessorWithoutCommandType(): void
    {
        $reflectionClass = new ReflectionClass(TestPreProcessorWithoutType::class);

        $this->expectExceptionMessage(sprintf(
            'PreProcessor \'%s\' has no linked commands.',
            TestPreProcessorWithoutType::class,
        ));

        $this->commandExtractor->fromPreProcessorReflectionClass($reflectionClass);
    }
}
