<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResponseExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestInterfaceQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Response\Ok;
use ADS\Util\MetadataExtractor\AttributeExtractor;
use ADS\Util\MetadataExtractor\ClassExtractor;
use ADS\Util\MetadataExtractor\InstanceExtractor;
use ADS\Util\MetadataExtractor\JsonSchemaExtractor;
use ADS\Util\MetadataExtractor\MetadataExtractor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

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

    public function testDefaultStatusCodeForInterfaceQueryFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceQuery::class);

        $defaultStatusCode = $this->responseExtractor->defaultStatusCodeFromReflectionClass($reflectionClass);

        $this->assertEquals(Response::HTTP_OK, $defaultStatusCode);
    }

    public function testDefaultStatusCodeForAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeQuery::class);

        $defaultStatusCode = $this->responseExtractor->defaultStatusCodeFromReflectionClass($reflectionClass);

        $this->assertEquals(Response::HTTP_OK, $defaultStatusCode);
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

    public function testNonQueryExtractor(): void
    {
        $reflectionClass = new ReflectionClass(JsonSchemaExtractor::class);

        $this->expectExceptionMessage('No metadata found');

        $this->responseExtractor->defaultStatusCodeFromReflectionClass($reflectionClass);
    }
}
