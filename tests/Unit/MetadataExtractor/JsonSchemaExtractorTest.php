<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\JsonSchemaExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\State\TestStates;
use EventEngine\JsonSchema\Type\ArrayType;
use EventEngine\JsonSchema\Type\ObjectType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function sprintf;

class JsonSchemaExtractorTest extends TestCase
{
    private JsonSchemaExtractor $jsonSchemaExtractor;

    protected function setUp(): void
    {
        $this->jsonSchemaExtractor = new JsonSchemaExtractor();
    }

    public function testRecordFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $schema = $this->jsonSchemaExtractor->fromReflectionClass($reflectionClass);

        $this->assertInstanceOf(ObjectType::class, $schema);
    }

    public function testCollectionFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestStates::class);

        $schema = $this->jsonSchemaExtractor->fromReflectionClass($reflectionClass);

        $this->assertInstanceOf(ArrayType::class, $schema);
    }

    public function testNonExistingSchemaExtractor(): void
    {
        $reflectionClass = new ReflectionClass(JsonSchemaExtractor::class);

        $this->expectExceptionMessage(
            sprintf(
                'No schema found for message \'%s\'. Implement the JsonSchemaAwareRecord interface.',
                $reflectionClass->getName(),
            ),
        );

        $this->jsonSchemaExtractor->fromReflectionClass($reflectionClass);
    }
}
