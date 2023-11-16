<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\ProjectorExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestAttributeProjector;
use ADS\Bundle\EventEngineBundle\Tests\Object\Projector\TestInterfaceProjector;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ProjectorExtractorTest extends TestCase
{
    private ProjectorExtractor $projectorExtractor;

    protected function setUp(): void
    {
        $this->projectorExtractor = new ProjectorExtractor();
    }

    public function testNameInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceProjector::class);

        $name = $this->projectorExtractor->nameFromReflectionClass($reflectionClass);

        $this->assertEquals('ProjectorInterfaceName', $name);
    }

    public function testVersionInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceProjector::class);

        $version = $this->projectorExtractor->versionFromReflectionClass($reflectionClass);

        $this->assertEquals('0.1.0', $version);
    }

    public function testNameAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeProjector::class);

        $name = $this->projectorExtractor->nameFromReflectionClass($reflectionClass);

        $this->assertEquals('ProjectorAttributeName', $name);
    }

    public function testVersionAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeProjector::class);

        $version = $this->projectorExtractor->versionFromReflectionClass($reflectionClass);

        $this->assertEquals('1.0.0', $version);
    }
}
