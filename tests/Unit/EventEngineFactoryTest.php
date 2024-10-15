<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Unit;

use ADS\Bundle\EventEngineBundle\Classes\ClassDivider;
use ADS\Bundle\EventEngineBundle\Classes\ClassMapper;
use ADS\Bundle\EventEngineBundle\EventEngineFactory;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\CommandExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ControllerExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\EventClassExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\PreProcessorExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ProjectorExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResolverExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResponseExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\StateClassExtractor;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestAttributeControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceAggregateCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Command\TestInterfaceControllerCommand;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestAttributeQuery;
use ADS\Bundle\EventEngineBundle\Tests\Object\Query\TestInterfaceQuery;
use ADS\Bundle\EventEngineBundle\Tests\Service\TestFlavour;
use EventEngine\Data\ImmutableRecord;
use EventEngine\EventEngine;
use EventEngine\JsonSchema\OpisJsonSchema;
use EventEngine\Logger\SimpleMessageEngine;
use EventEngine\Messaging\CommandDispatchResult;
use EventEngine\Messaging\CommandDispatchResultCollection;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Persistence\InMemoryConnection;
use EventEngine\Prooph\V7\EventStore\InMemoryMultiModelStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use TeamBlue\Util\MetadataExtractor\AttributeExtractor;
use TeamBlue\Util\MetadataExtractor\ClassExtractor;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

/** @SuppressWarnings(PHPMD.CouplingBetweenObjects) */
class EventEngineFactoryTest extends TestCase
{
    private const CONFIG = [
        'commandMap' => [],
        'eventMap' => [],
        'compiledCommandRouting' => [],
        'commandPreProcessors' => [],
        'commandControllers' => [],
        'aggregateDescriptions' => [],
        'eventRouting' => [],
        'compiledProjectionDescriptions' => [],
        'compiledQueryDescriptions' => [],
        'queryMap' => [],
        'responseTypes' => [],
        'inputTypes' => [],
        'writeModelStreamName' => 'event_stream',
        'autoPublish' => true,
        'autoProjecting' => false,
        'forwardMetadata' => false,
    ];

    private MockObject $cache;

    private ContainerInterface $container;

    private EventEngineFactory $eventEngineFactory;

    protected function setUp(): void
    {
        $metadataExtractor = new MetadataExtractor(
            new AttributeExtractor(),
            new ClassExtractor(),
        );
        $classDivider = new ClassDivider([__DIR__ . '/../Object/']);
        $eventClassExtractor = new EventClassExtractor($metadataExtractor);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnCallback(static fn ($class): object => new $class());

        $this->eventEngineFactory = new EventEngineFactory(
            new OpisJsonSchema(),
            new TestFlavour(),
            $this->inMemoryMultiModelStore(),
            $this->createMock(SimpleMessageEngine::class),
            $this->container,
            $this->createMock(MessageProducer::class),
            $this->cache,
            new ControllerExtractor($metadataExtractor),
            new AggregateCommandExtractor($metadataExtractor),
            new ResolverExtractor($metadataExtractor),
            new ResponseExtractor($metadataExtractor),
            new StateClassExtractor($metadataExtractor),
            $eventClassExtractor,
            new ProjectorExtractor($metadataExtractor),
            new ClassMapper(
                $eventClassExtractor,
                new PreProcessorExtractor($metadataExtractor),
                new CommandExtractor($metadataExtractor),
                new AggregateCommandExtractor($metadataExtractor),
                $classDivider->commandClasses(),
                $classDivider->aggregateCommandClasses(),
                $classDivider->aggregateClasses(),
                $classDivider->preProcessorClasses(),
            ),
            $classDivider->commandClasses(),
            $classDivider->controllerCommandClasses(),
            $classDivider->aggregateCommandClasses(),
            $classDivider->queryClasses(),
            $classDivider->eventClasses(),
            $classDivider->aggregateClasses(),
            $classDivider->projectorClasses(),
            $classDivider->typeClasses(),
            $classDivider->descriptionClasses(),
            $classDivider->listenerClasses(),
            'develop',
            true,
        );
    }

    private function inMemoryMultiModelStore(): InMemoryMultiModelStore
    {
        $inMemoryConnection = new InMemoryConnection();
        $inMemoryConnection['documents'] = ['test_aggregate_state' => ['test' => ['state' => [], 'version' => 1]]];
        $inMemoryConnection['event_streams'] = ['test_aggregate_stream' => []];
        $inMemoryConnection['events'] = ['test_aggregate_stream' => []];

        return InMemoryMultiModelStore::fromConnection($inMemoryConnection);
    }

    public function testFactory(): void
    {
        $eventEngine = ($this->eventEngineFactory)();

        $this->assertInstanceOf(EventEngine::class, $eventEngine);
    }

    public function testCache(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn(self::CONFIG);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('event_engine_config')
            ->willReturn($cacheItem);

        $eventEngine = ($this->eventEngineFactory)();

        $this->assertInstanceOf(EventEngine::class, $eventEngine);
    }

    public function testInterfaceAggregateCommand(): void
    {
        $eventEngine = ($this->eventEngineFactory)();

        $result = $eventEngine->dispatch(TestInterfaceAggregateCommand::class, ['test' => 'test']);

        $this->assertInstanceOf(CommandDispatchResult::class, $result);
        $this->assertCount(1, $result->recordedEvents());
        $this->assertEquals('test', $result->effectedAggregateId());
    }

    public function testAttributeAggregateCommand(): void
    {
        $eventEngine = ($this->eventEngineFactory)();

        $result = $eventEngine->dispatch(TestAttributeAggregateCommand::class, ['test' => 'test']);

        $this->assertInstanceOf(CommandDispatchResult::class, $result);
        $this->assertCount(1, $result->recordedEvents());
        $this->assertEquals('test', $result->effectedAggregateId());
    }

    public function testInterfaceControllerCommand(): void
    {
        $eventEngine = ($this->eventEngineFactory)();

        $result = $eventEngine->dispatch(TestInterfaceControllerCommand::class, ['test' => 'test']);

        $this->assertInstanceOf(CommandDispatchResultCollection::class, $result);
        /** @var array{0: CommandDispatchResult} $results */
        $results = $result->toArray();
        $this->assertCount(1, $results);
        $this->assertCount(0, $results[0]->recordedEvents());
    }

    public function testAttributeControllerCommand(): void
    {
        $eventEngine = ($this->eventEngineFactory)();

        $result = $eventEngine->dispatch(TestAttributeControllerCommand::class, ['test' => 'test']);

        $this->assertInstanceOf(CommandDispatchResultCollection::class, $result);
        /** @var array{0: CommandDispatchResult} $results */
        $results = $result->toArray();
        $this->assertCount(1, $results);
        $this->assertCount(0, $results[0]->recordedEvents());
    }

    public function testInterfaceQuery(): void
    {
        $eventEngine = ($this->eventEngineFactory)();

        /** @var ImmutableRecord $result */
        $result = $eventEngine->dispatch(TestInterfaceQuery::class, ['test' => 'test']);

        $results = $result->toArray();
        $this->assertArrayHasKey('id', $results);
        $this->assertEquals('test', $results['id']);
    }

    public function testAttributeQuery(): void
    {
        $eventEngine = ($this->eventEngineFactory)();

        /** @var ImmutableRecord $result */
        $result = $eventEngine->dispatch(TestAttributeQuery::class, ['test' => 'test']);

        $results = $result->toArray();
        $this->assertArrayHasKey('id', $results);
        $this->assertEquals('test', $results['id']);
    }
}
