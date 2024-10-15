<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\QueueableExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TeamBlue\Util\MetadataExtractor\AttributeExtractor;
use TeamBlue\Util\MetadataExtractor\ClassExtractor;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

class QueueableExtractorTest extends TestCase
{
    private QueueableExtractor $queueableExtractor;

    protected function setUp(): void
    {
        $this->queueableExtractor = new QueueableExtractor(
            new MetadataExtractor(
                new AttributeExtractor(),
                new ClassExtractor(),
            ),
        );
    }

    public function testQueueAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $queue = $this->queueableExtractor->queueFromReflectionClass($reflectionClass);

        $this->assertTrue($queue);
    }

    public function testQueueInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $queue = $this->queueableExtractor->queueFromReflectionClass($reflectionClass);

        $this->assertTrue($queue);
    }

    public function testMaxRetriesAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $maxRetries = $this->queueableExtractor->maxRetriesFromReflectionClass($reflectionClass);

        $this->assertEquals(10, $maxRetries);
    }

    public function testMaxRetriesInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $maxRetries = $this->queueableExtractor->maxRetriesFromReflectionClass($reflectionClass);

        $this->assertEquals(10, $maxRetries);
    }

    public function testDelayInMilliSecondsAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $delayInMilliseconds = $this->queueableExtractor->delayInMillisecondsFromReflectionClass($reflectionClass);

        $this->assertEquals(1000, $delayInMilliseconds);
    }

    public function testDelayInMilliSecondsInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $delayInMilliseconds = $this->queueableExtractor->delayInMillisecondsFromReflectionClass($reflectionClass);

        $this->assertEquals(1000, $delayInMilliseconds);
    }

    public function testMultiplierAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $multiplier = $this->queueableExtractor->multiplierFromReflectionClass($reflectionClass);

        $this->assertEquals(8, $multiplier);
    }

    public function testMultiplierInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $multiplier = $this->queueableExtractor->multiplierFromReflectionClass($reflectionClass);

        $this->assertEquals(8, $multiplier);
    }

    public function testMaxDelayInMilliSecondsAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $maxDelayInMilliseconds = $this->queueableExtractor
            ->maxDelayInMillisecondsFromReflectionClass($reflectionClass);

        $this->assertEquals(10 * 60 * 1000, $maxDelayInMilliseconds);
    }

    public function testMaxDelayInMilliSecondsInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $maxDelayInMilliseconds = $this->queueableExtractor
            ->maxDelayInMillisecondsFromReflectionClass($reflectionClass);

        $this->assertEquals(10 * 60 * 1000, $maxDelayInMilliseconds);
    }

    public function testSendToLinkedFailureTransportAttributeFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestAttributeAggregateCommand::class);

        $sendToLinkedFailureTransport = $this->queueableExtractor
            ->sendToLinkedFailureTransportFromReflectionClass($reflectionClass);

        $this->assertFalse($sendToLinkedFailureTransport);
    }

    public function testSendToLinkedFailureTransportInterfaceFromReflectionClass(): void
    {
        $reflectionClass = new ReflectionClass(TestInterfaceAggregateCommand::class);

        $sendToLinkedFailureTransport = $this->queueableExtractor
            ->sendToLinkedFailureTransportFromReflectionClass($reflectionClass);

        $this->assertFalse($sendToLinkedFailureTransport);
    }
}
