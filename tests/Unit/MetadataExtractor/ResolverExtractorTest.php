<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Query as QueryAttribute;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\JsonSchemaExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResolverExtractor;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestInterfaceQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Resolver\TestResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function sprintf;

class ResolverExtractorTest extends TestCase
{
    private ResolverExtractor $resolverExtractor;

    protected function setUp(): void
    {
        $this->resolverExtractor = new ResolverExtractor();
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

        $this->expectExceptionMessage(
            sprintf(
                'No implementation of \'%s\' found or attribute \'%s\' added for \'%s\'.',
                Query::class,
                QueryAttribute::class,
                $reflectionClass->getName(),
            ),
        );

        $this->resolverExtractor->fromReflectionClass($reflectionClass);
    }
}
