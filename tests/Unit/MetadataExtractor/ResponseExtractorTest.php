<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AttributeExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ClassExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\InstanceExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\JsonSchemaExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\MetadataExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResponseExtractor;
use ADS\Bundle\EventEngineBundle\Tests\FailingObject\Query\TestAttributeQueryWithEmptyResponse;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestInterfaceQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\Ok;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function sprintf;

class ResponseExtractorTest extends TestCase
{
    private ResponseExtractor $responseExtractor;

    protected function setUp(): void
    {
        $this->responseExtractor = new ResponseExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
                new InstanceExtractor(),
            ),
        );
    }

    public function testResponsesPerStatusCodeForInterfaceQueryFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceQuery::class);

        $responseClassesPerStatusCode = $this->responseExtractor
            ->responseClassesPerStatusCodeFromReflectionClass($reflectionClass);

        $this->assertCount(1, $responseClassesPerStatusCode);
    }

    public function testResponsesPerStatusCodeForAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeQuery::class);

        $responseClassesPerStatusCode = $this->responseExtractor
            ->responseClassesPerStatusCodeFromReflectionClass($reflectionClass);

        $this->assertCount(2, $responseClassesPerStatusCode);
    }

    public function testDefaultStatusCodeForInterfaceQueryFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceQuery::class);

        $defaultStatusCode = $this->responseExtractor->defaultStatusCodeFromReflectionClass($reflectionClass);

        $this->assertNull($defaultStatusCode);
    }

    public function testDefaultStatusCodeForAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeQuery::class);

        $defaultStatusCode = $this->responseExtractor->defaultStatusCodeFromReflectionClass($reflectionClass);

        $this->assertEquals(200, $defaultStatusCode);
    }

    public function testDefaultResponseClassForInterfaceQueryFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceQuery::class);

        $defaultResponseClass = $this->responseExtractor->defaultResponseClassFromReflectionClass($reflectionClass);

        $this->assertEquals(Ok::class, $defaultResponseClass);
    }

    public function testDefaultResponseClassForAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeQuery::class);

        $defaultResponseClass = $this->responseExtractor->defaultResponseClassFromReflectionClass($reflectionClass);

        $this->assertEquals(Ok::class, $defaultResponseClass);
    }

    public function testNoDefaultResponseClassFoundFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeQueryWithEmptyResponse::class);

        $this->expectExceptionMessage(
            sprintf(
                'No default response class found for message \'%s\'.',
                $reflectionClass->getName(),
            ),
        );

        $this->responseExtractor->defaultResponseClassFromReflectionClass($reflectionClass);
    }

    public function testNonQueryExtractor(): void
    {
        $reflectionClass = new ReflectionClass(JsonSchemaExtractor::class);

        $this->expectExceptionMessage('No metadata found');

        $this->responseExtractor->defaultStatusCodeFromReflectionClass($reflectionClass);
    }
}
