<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Classes\ClassMapper;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ControllerExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\EventClassExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ProjectorExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResolverExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\ResponseExtractor;
use ADS\Bundle\EventEngineBundle\MetadataExtractor\StateClassExtractor;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use ADS\JsonImmutableObjects\MetadataExtractor\JsonSchemaExtractor;
use Closure;
use EventEngine\Commanding\CommandProcessorDescription;
use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\JsonSchema\Type;
use EventEngine\Logger\SimpleMessageEngine;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Persistence\MultiModelStore;
use EventEngine\Persistence\Stream;
use EventEngine\Runtime\Flavour;
use EventEngine\Runtime\Oop\FlavourHint;
use EventEngine\Schema\PayloadSchema;
use EventEngine\Schema\ResponseTypeSchema;
use EventEngine\Schema\Schema;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

use function array_diff_key;
use function array_flip;
use function array_map;
use function array_unique;
use function is_callable;
use function is_string;
use function sprintf;

final class EventEngineFactory
{
    private const ENVIRONMENT_MAP = [
        'staging' => EventEngine::ENV_DEV,
        'acceptance' => EventEngine::ENV_PROD,
        'develop' => EventEngine::ENV_DEV,
    ];

    private readonly string $environment;

    private readonly JsonSchemaExtractor $jsonSchemaExtractor;

    /**
     * @param array<class-string<JsonSchemaAwareRecord>> $commands
     * @param array<class-string<JsonSchemaAwareRecord>> $controllerCommands
     * @param array<class-string<JsonSchemaAwareRecord>> $aggregateCommands
     * @param array<class-string<JsonSchemaAwareRecord>> $queries
     * @param array<class-string<JsonSchemaAwareRecord>> $events
     * @param array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> $aggregates
     * @param array<class-string> $projectors
     * @param array<class-string<JsonSchemaAwareRecord>> $types
     * @param array<class-string<EventEngineDescription>> $descriptions
     * @param array<class-string> $listeners
     */
    public function __construct(
        private readonly Schema $schema,
        private readonly Flavour $flavour,
        private readonly MultiModelStore $multiModelStore,
        private readonly SimpleMessageEngine $simpleMessageEngine,
        private readonly ContainerInterface $container,
        private readonly MessageProducer|null $eventQueue,
        private readonly CacheItemPoolInterface $cache,
        private readonly ControllerExtractor $controllerExtractor,
        private readonly AggregateCommandExtractor $aggregateCommandExtractor,
        private readonly ResolverExtractor $resolverExtractor,
        private readonly ResponseExtractor $responseExtractor,
        private readonly StateClassExtractor $stateClassExtractor,
        private readonly EventClassExtractor $eventClassExtractor,
        private readonly ProjectorExtractor $projectorExtractor,
        private readonly ClassMapper $classMapper,
        private readonly array $commands,
        private readonly array $controllerCommands,
        private readonly array $aggregateCommands,
        private readonly array $queries,
        private readonly array $events,
        private readonly array $aggregates,
        private readonly array $projectors,
        private readonly array $types,
        private readonly array $descriptions,
        private readonly array $listeners,
        string $environment,
        private readonly bool $debug,
    ) {
        $this->environment = $this->mapEnvironment($environment);
        $this->jsonSchemaExtractor = new JsonSchemaExtractor();
    }

    private function mapEnvironment(string $environment): string
    {
        if (! isset(self::ENVIRONMENT_MAP[$environment])) {
            return $environment;
        }

        return self::ENVIRONMENT_MAP[$environment];
    }

    public function __invoke(): EventEngine
    {
        $eventEngine = $this->cachedEventEngine();

        if ($eventEngine instanceof EventEngine) {
            return $eventEngine;
        }

        $eventEngine = new EventEngine($this->schema);

        $this
            ->registerCommands($eventEngine)
            ->registerAndResolveQueries($eventEngine)
            ->registerEvents($eventEngine)
            ->registerTypes($eventEngine)
            ->configureListeners($eventEngine)
            ->configureProjectors($eventEngine)
            ->loadDescriptions($eventEngine)
            ->configurePreProcessorsAndControllerCommands($eventEngine)
            ->configurePreProcessorsAndAggregates($eventEngine)
            ->configurePreProcessorsAndCommands($eventEngine);

        $eventEngine->disableAutoProjecting();

        $eventEngine->initialize(
            $this->flavour,
            $this->multiModelStore,
            $this->simpleMessageEngine,
            $this->container,
            null,
            $this->eventQueue,
        )
            ->bootstrap(
                $this->environment,
                $this->debug,
            );

        $this->cache->save(
            $this->cache
                ->getItem('event_engine_config')
                ->set($eventEngine->compileCacheableConfig()),
        );

        return $eventEngine;
    }

    private function registerCommands(EventEngine $eventEngine): self
    {
        foreach ($this->commands as $commandClass) {
            /** @var PayloadSchema $schema */
            $schema = $this->jsonSchemaExtractor->fromReflectionClass(new ReflectionClass($commandClass));
            $eventEngine->registerCommand($commandClass, $schema);
        }

        return $this;
    }

    private function registerAndResolveQueries(EventEngine $eventEngine): self
    {
        foreach ($this->queries as $queryClass) {
            $reflectionClass = new ReflectionClass($queryClass);
            /** @var PayloadSchema $schema */
            $schema = $this->jsonSchemaExtractor->fromReflectionClass($reflectionClass);
            $resolver = $this->resolverExtractor->fromReflectionClass($reflectionClass);
            $responseClass = $this->responseExtractor->defaultResponseClassFromReflectionClass($reflectionClass);

            $eventEngine->registerQuery($queryClass, $schema)
                ->resolveWith($resolver)
                ->setReturnType($this->returnType($responseClass));
        }

        return $this;
    }

    /** @param class-string<JsonSchemaAwareRecord|JsonSchemaAwareCollection> $responseClass */
    private function returnType(string $responseClass): ResponseTypeSchema
    {
        $responseReflectionClass = new ReflectionClass($responseClass);

        if ($responseReflectionClass->implementsInterface(JsonSchemaAwareRecord::class)) {
            // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
            /** @var class-string<JsonSchemaAwareRecord> $responseClass */
            /** @var ResponseTypeSchema $schema */
            $schema = $responseClass::__schema();

            return $schema;
        }

        if ($responseReflectionClass->implementsInterface(JsonSchemaAwareCollection::class)) {
            // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
            /** @var class-string<JsonSchemaAwareCollection> $responseClass */
            /** @var Type $itemSchema */
            $itemSchema = $responseClass::__itemSchema();

            return JsonSchema::array($itemSchema);
        }

        throw new RuntimeException(
            sprintf(
                "Response class '%s' is not a JsonSchemaAwareRecord or JsonSchemaAwareCollection.",
                $responseClass,
            ),
        );
    }

    private function registerEvents(EventEngine $eventEngine): self
    {
        foreach ($this->events as $eventClass) {
            /** @var PayloadSchema $schema */
            $schema = $this->jsonSchemaExtractor->fromReflectionClass(new ReflectionClass($eventClass));
            $eventEngine->registerEvent($eventClass, $schema);
        }

        return $this;
    }

    private function registerTypes(EventEngine $eventEngine): self
    {
        $types = array_unique(
            [
                ...array_map(
                    fn (string $aggregateClass): string => $this->stateClassExtractor
                        ->fromAggregateRootReflectionClass(new ReflectionClass($aggregateClass)),
                    $this->aggregates,
                ),
                ...array_map(
                    /** @param ReflectionClass<object> $projectorRelfectionClass */
                    fn (string $projectorClass): string => $this->stateClassExtractor
                        ->fromProjectorReflectionClass(new ReflectionClass($projectorClass)),
                    $this->projectors,
                ),
                ...$this->types,
            ],
        );

        foreach ($types as $type) {
            $eventEngine->registerType($type);
        }

        return $this;
    }

    private function configureListeners(EventEngine $eventEngine): self
    {
        foreach ($this->listeners as $listenerClass) {
            $eventClasses = $this->eventClassExtractor
                ->fromListenerReflectionClass(new ReflectionClass($listenerClass));

            if (is_string($eventClasses)) {
                $eventClasses = [$eventClasses];
            }

            foreach ($eventClasses as $eventClass) {
                $eventEngine->on($eventClass, $listenerClass);
            }
        }

        return $this;
    }

    private function configureProjectors(EventEngine $eventEngine): self
    {
        foreach ($this->projectors as $projectorClass) {
            $reflectionClass = new ReflectionClass($projectorClass);
            $events = $this->eventClassExtractor->fromProjectorReflectionClass($reflectionClass);
            $name = $this->projectorExtractor->nameFromReflectionClass($reflectionClass);
            $version = $this->projectorExtractor->versionFromReflectionClass($reflectionClass);
            $aggregateRootClasses = $this->classMapper->aggregateRootClassesFromEventClasses($events);
            $aggregateStreams = $this->aggregateStreamsFromAggregateRootClasses($aggregateRootClasses);

            $eventEngine
                ->watch(...$aggregateStreams)
                ->with($name, $projectorClass, $version)
                ->filterEvents($events);
        }

        return $this;
    }

    /**
     * @param array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> $aggregateRootClasses
     *
     * @return array<Stream>
     */
    private function aggregateStreamsFromAggregateRootClasses(
        array $aggregateRootClasses,
    ): array {
        return array_map(
            static fn ($aggregateRootClass): Stream => Stream::ofLocalProjection(
                EventEngineUtil::fromAggregateClassToStreamName($aggregateRootClass),
            ),
            $aggregateRootClasses,
        );
    }

    private function loadDescriptions(EventEngine $eventEngine): self
    {
        foreach ($this->descriptions as $descriptionClass) {
            $eventEngine->load($descriptionClass);
        }

        return $this;
    }

    private function configurePreProcessorsAndControllerCommands(EventEngine $eventEngine): self
    {
        $preProcessorMapping = $this->classMapper->commandPreProcessorMapping();

        foreach ($this->controllerCommands as $controllerCommandClass) {
            if (isset($preProcessorMapping[$controllerCommandClass])) {
                $preProcessorClasses = $preProcessorMapping[$controllerCommandClass];
                foreach ($preProcessorClasses as $preProcessorClass) {
                    $eventEngine->preProcess($controllerCommandClass, $preProcessorClass);
                }
            }

            $eventEngine->passToController(
                $controllerCommandClass,
                $this->controllerExtractor->fromReflectionClass(new ReflectionClass($controllerCommandClass)),
            );
        }

        return $this;
    }

    private function configurePreProcessorsAndAggregates(EventEngine $eventEngine): self
    {
        $preProcessorMapping = $this->classMapper->commandPreProcessorMapping();
        $aggregateMapping = $this->classMapper->commandAggregateMapping();

        foreach ($this->aggregateCommands as $aggregateCommandClass) {
            $aggregateCommandReflectionClass = new ReflectionClass($aggregateCommandClass);
            $commandProcessor = $eventEngine->process($aggregateCommandClass);

            if (isset($preProcessorMapping[$aggregateCommandClass])) {
                $preProcessorClasses = $preProcessorMapping[$aggregateCommandClass];
                foreach ($preProcessorClasses as $preProcessorClass) {
                    $commandProcessor->preProcess($preProcessorClass);
                }
            }

            if (! isset($aggregateMapping[$aggregateCommandClass])) {
                throw new RuntimeException(
                    sprintf(
                        "No aggregate root found for aggregate command '%s'.",
                        $aggregateCommandClass,
                    ),
                );
            }

            $aggregateRootClass = $aggregateMapping[$aggregateCommandClass];

            $newAggregateRoot = $this->aggregateCommandExtractor
                ->newFromReflectionClass($aggregateCommandReflectionClass);

            $this
                ->handleCommand(
                    $commandProcessor,
                    $aggregateRootClass,
                    $aggregateCommandReflectionClass,
                    $newAggregateRoot,
                )
                ->handleEvents(
                    $commandProcessor,
                    $aggregateCommandReflectionClass,
                )
                ->handleContextProviders(
                    $commandProcessor,
                    $aggregateCommandClass,
                )
                ->handleServices(
                    $commandProcessor,
                    $aggregateCommandClass,
                )
                ->handleStorage(
                    $commandProcessor,
                    $aggregateRootClass,
                    $newAggregateRoot,
                );
        }

        return $this;
    }

    /**
     * @param class-string<AggregateRoot<JsonSchemaAwareRecord>> $aggregateRootClass
     * @param ReflectionClass<JsonSchemaAwareRecord> $aggregateCommandReflectionClass
     */
    private function handleCommand(
        CommandProcessorDescription $commandProcessor,
        string $aggregateRootClass,
        ReflectionClass $aggregateCommandReflectionClass,
        bool $newAggregateRoot,
    ): self {
        $aggregateRootMethod = $newAggregateRoot ? 'withNew' : 'withExisting';
        $aggregateIdentifierMapping = $this->classMapper->aggregateIdentifierMapping();

        if (! isset($aggregateIdentifierMapping[$aggregateRootClass])) {
            throw new RuntimeException(
                sprintf(
                    "No aggregate identifier found for aggregate root '%s'.",
                    $aggregateRootClass,
                ),
            );
        }

        $commandProcessor
            ->$aggregateRootMethod($aggregateRootClass)
            ->identifiedBy($aggregateIdentifierMapping[$aggregateRootClass])
            ->handle($this->handle($aggregateRootClass, $aggregateCommandReflectionClass, $newAggregateRoot));

        return $this;
    }

    /** @param ReflectionClass<JsonSchemaAwareRecord> $aggregateCommandReflectionClass */
    private function handleEvents(
        CommandProcessorDescription $commandProcessor,
        ReflectionClass $aggregateCommandReflectionClass,
    ): self {
        $events = $this->eventClassExtractor->fromAggregateCommandReflectionClass($aggregateCommandReflectionClass);

        foreach ($events as $eventClass) {
            $commandProcessor
                ->recordThat($eventClass)
                ->apply(static fn () => FlavourHint::useAggregate());
        }

        return $this;
    }

    /** @param class-string<JsonSchemaAwareRecord> $commandClass */
    private function handleContextProviders(
        CommandProcessorDescription $commandProcessor,
        string $commandClass,
    ): self {
        $commandContextProviderMapping = $this->classMapper->commandContextProviderMapping();

        if (! isset($commandContextProviderMapping[$commandClass])) {
            throw new RuntimeException(
                sprintf(
                    "No context providers found for command '%s'.",
                    $commandClass,
                ),
            );
        }

        $contextProviders = $commandContextProviderMapping[$commandClass];

        foreach ($contextProviders as $contextProviderId) {
            $commandProcessor->provideContext($contextProviderId);
        }

        return $this;
    }

    /** @param class-string<JsonSchemaAwareRecord> $commandClass */
    private function handleServices(
        CommandProcessorDescription $commandProcessor,
        string $commandClass,
    ): self {
        $commandServiceMapping = $this->classMapper->commandServiceMapping();

        if (! isset($commandServiceMapping[$commandClass])) {
            throw new RuntimeException(
                sprintf(
                    "No services found for command '%s'.",
                    $commandClass,
                ),
            );
        }

        $services = $commandServiceMapping[$commandClass];

        foreach ($services as $serviceId) {
            $commandProcessor->provideService($serviceId);
        }

        return $this;
    }

    /** @param class-string<AggregateRoot<JsonSchemaAwareRecord>> $aggregateRootClass */
    private function handleStorage(
        CommandProcessorDescription $commandProcessor,
        string $aggregateRootClass,
        bool $newAggregateRoot,
    ): self {
        if (! $newAggregateRoot) {
            return $this;
        }

        $aggregateName = EventEngineUtil::fromAggregateClassToAggregateName($aggregateRootClass);

        $commandProcessor
            ->storeEventsIn(EventEngineUtil::fromAggregateNameToStreamName($aggregateName))
            ->storeStateIn(EventEngineUtil::fromAggregateNameToDocumentStoreName($aggregateName));

        return $this;
    }

    /**
     * @param class-string<AggregateRoot<JsonSchemaAwareRecord>> $aggregateRootClass
     * @param ReflectionClass<JsonSchemaAwareRecord> $aggregateCommandReflectionClass
     *
     * @return array{class-string, string}|Closure
     */
    private function handle(
        string $aggregateRootClass,
        ReflectionClass $aggregateCommandReflectionClass,
        bool $newAggregateRoot,
    ): array|Closure {
        if (! $newAggregateRoot) {
            return static fn () => FlavourHint::useAggregate();
        }

        $aggregateMethod = $this->aggregateCommandExtractor->aggregateMethodFromReflectionClass(
            $aggregateCommandReflectionClass,
        );

        $handle = [
            $aggregateRootClass,
            $aggregateMethod,
        ];

        if (is_callable($handle)) {
            return $handle;
        }

        throw new RuntimeException(
            sprintf(
                "Aggregate method '%s' for aggregate root '%s' is not callable.",
                $aggregateMethod,
                $aggregateRootClass,
            ),
        );
    }

    private function configurePreProcessorsAndCommands(EventEngine $eventEngine): self
    {
        $preProcessorMapping = $this->classMapper->commandPreProcessorMapping();
        $commandPreProcessorMapping = array_diff_key(
            $preProcessorMapping,
            array_flip($this->aggregateCommands),
            array_flip($this->controllerCommands),
        );

        foreach ($commandPreProcessorMapping as $commandClass => $preProcessorClasses) {
            foreach ($preProcessorClasses as $preProcessorClass) {
                $eventEngine
                    ->process($commandClass)
                    ->preProcess($preProcessorClass)
                    ->withNew('test')
                    ->handle(static fn () => FlavourHint::useAggregate());
            }
        }

        return $this;
    }

    private function cachedEventEngine(): EventEngine|null
    {
        $cacheConfig = $this->cache->getItem('event_engine_config');

        if ($cacheConfig->isHit()) {
            /** @var array<mixed> $config */
            $config = $cacheConfig->get();

            return EventEngine::fromCachedConfig(
                $config,
                $this->schema,
                $this->flavour,
                $this->multiModelStore,
                $this->simpleMessageEngine,
                $this->container,
                null,
                $this->eventQueue,
            )
                ->bootstrap(
                    $this->environment,
                    $this->debug,
                );
        }

        return null;
    }
}
