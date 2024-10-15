<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResolverExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestInterfaceQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Resolver\TestResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TeamBlue\JsonImmutableObjects\MetadataExtractor\JsonSchemaExtractor;
use TeamBlue\Util\MetadataExtractor\AttributeExtractor;
use TeamBlue\Util\MetadataExtractor\ClassExtractor;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

class ResolverExtractorTest extends TestCase
{
    private ResolverExtractor $resolverExtractor;

    protected function setUp(): void
    {
        $this->resolverExtractor = new ResolverExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
            ),
        );
    }

    public function testInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceQuery::class);

        $controller = $this->resolverExtractor->fromReflectionClass($reflectionClass);

        $this->assertEquals(TestResolver::class, $controller);
    }

    public function testAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeQuery::class);

        $controller = $this->resolverExtractor->fromReflectionClass($reflectionClass);

        $this->assertEquals(TestResolver::class, $controller);
    }

    public function testNonQueryExtractor(): void
    {
        $reflectionClass = new ReflectionClass(JsonSchemaExtractor::class);

        $this->expectExceptionMessage('No metadata found');

        $this->resolverExtractor->fromReflectionClass($reflectionClass);
    }
}
