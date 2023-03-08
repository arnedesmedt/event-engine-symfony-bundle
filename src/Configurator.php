<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Command\Command;
use ADS\Bundle\EventEngineBundle\Command\ControllerCommand;
use ADS\Bundle\EventEngineBundle\Event\Event;
use ADS\Bundle\EventEngineBundle\Event\Listener;
use ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor;
use ADS\Bundle\EventEngineBundle\Projector\Projector;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\Commanding\CommandProcessorDescription;
use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Logger\SimpleMessageEngine;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Persistence\MultiModelStore;
use EventEngine\Persistence\Stream;
use EventEngine\Runtime\Flavour;
use EventEngine\Runtime\Oop\FlavourHint;
use EventEngine\Schema\PayloadSchema;
use EventEngine\Schema\ResponseTypeSchema;
use EventEngine\Schema\TypeSchema;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_shift;
use function array_unique;
use function class_implements;
use function in_array;
use function is_callable;
use function is_int;
use function is_string;
use function reset;
use function sprintf;

final class Configurator
{
    private const ENVIRONMENT_MAP = [
        'staging' => EventEngine::ENV_DEV,
        'acceptance' => EventEngine::ENV_PROD,
        'develop' => EventEngine::ENV_DEV,
    ];
    /** @readonly */
    private string $environment;

    /** @var array<class-string<AggregateCommand>, class-string<AggregateRoot<JsonSchemaAwareRecord>>> */
    private array $commandAggregateMapping = [];
    /** @var array<class-string<AggregateCommand>, array<class-string<Event>>> */
    private array $commandEventMapping = [];
    /** @var array<class-string<AggregateCommand>, array<class-string|string>> */
    private array $commandServiceMapping = [];
    /** @var array<class-string<Command>, class-string<PreProcessor>> */
    private array $commandPreProcessorMapping = [];
    /** @var array<class-string<AggregateRoot<JsonSchemaAwareRecord>>, string> */
    private array $aggregateIdentifierMapping = [];

    /**
     * @param array<class-string<Command>> $commandClasses
     * @param array<class-string<Query>> $queryClasses
     * @param array<class-string<Event>> $eventClasses
     * @param array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> $aggregateClasses
     * @param array<class-string> $typeClasses
     * @param array<class-string<Listener>> $listenerClasses
     * @param array<class-string<Projector>> $projectorClasses
     * @param array<class-string<PreProcessor>> $preProcessorClasses
     * @param array<class-string<EventEngineDescription>> $descriptionServices
     */
    public function __construct(
        private Flavour $flavour,
        private MultiModelStore $multiModelStore,
        private SimpleMessageEngine $simpleMessageEngine,
        private ContainerInterface $container,
        string $environment,
        private bool $debug,
        private array $commandClasses,
        private array $queryClasses,
        private array $eventClasses,
        private array $aggregateClasses,
        private array $typeClasses,
        private array $listenerClasses,
        private array $projectorClasses,
        private array $preProcessorClasses,
        private array $descriptionServices,
        private MessageProducer|null $eventQueue,
    ) {
        $this->environment = $this->mapEnvironment($environment);
    }

    public function __invoke(EventEngine $eventEngine): void
    {
        $this
            ->registerCommands($eventEngine)
            ->registerQueries($eventEngine)
            ->registerEvents($eventEngine)
            ->registerTypes($eventEngine)
            ->registerListeners($eventEngine)
            ->registerProjectors($eventEngine)
            ->registerDescriptions($eventEngine)
            ->registerPreProcessorsAndAggregates($eventEngine);

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
    }

    private function registerCommands(EventEngine $eventEngine): self
    {
        foreach ($this->commandClasses as $commandClass) {
            /** @var PayloadSchema $schema */
            $schema = self::schemaFromMessage($commandClass);
            $eventEngine->registerCommand($commandClass, $schema);

            $implementedClasses = class_implements($commandClass);
            if (! $implementedClasses || ! in_array(ControllerCommand::class, $implementedClasses)) {
                continue;
            }

            // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.NoAssignment
            /** @var class-string<ControllerCommand> $commandClass */
            $eventEngine->passToController($commandClass, $commandClass::__controller());
        }

        return $this;
    }

    private function registerQueries(EventEngine $eventEngine): self
    {
        foreach ($this->queryClasses as $queryClass) {
            /** @var PayloadSchema $schema */
            $schema = self::schemaFromMessage($queryClass);
            /** @var class-string<JsonSchemaAwareRecord|JsonSchemaAwareCollection> $responseClass */
            $responseClass = $queryClass::__defaultResponseClass();
            $reflectionResponseClass = new ReflectionClass($responseClass);
            /** @var ResponseTypeSchema $typeSchema */
            $typeSchema = $reflectionResponseClass->implementsInterface(JsonSchemaAwareRecord::class)
                ? $responseClass::__schema()
                : JsonSchema::array($responseClass::__itemSchema());
            $eventEngine->registerQuery($queryClass, $schema)
                ->resolveWith($queryClass::__resolver())
                ->setReturnType($typeSchema);
        }

        return $this;
    }

    private function registerEvents(EventEngine $eventEngine): self
    {
        foreach ($this->eventClasses as $event) {
            /** @var PayloadSchema $schema */
            $schema = self::schemaFromMessage($event);
            $eventEngine->registerEvent($event, $schema);
        }

        return $this;
    }

    private function registerTypes(EventEngine $eventEngine): self
    {
        $types = array_unique(
            [
                ...array_map(
                    static fn ($aggregateClass) => $aggregateClass::stateClass(),
                    $this->aggregateClasses,
                ),
                ...array_map(
                    static fn ($projectorClass) => $projectorClass::stateClassName(),
                    $this->projectorClasses,
                ),
                ...$this->typeClasses,
            ],
        );

        foreach ($types as $type) {
            $eventEngine->registerType($type);
        }

        return $this;
    }

    private function registerListeners(EventEngine $eventEngine): self
    {
        foreach ($this->listenerClasses as $listenerClass) {
            $eventClasses = $listenerClass::__handleEvents();

            if (is_string($eventClasses)) {
                $eventClasses = [$eventClasses];
            }

            foreach ($eventClasses as $eventClass) {
                $eventEngine->on($eventClass, $listenerClass);
            }
        }

        return $this;
    }

    private function registerProjectors(EventEngine $eventEngine): self
    {
        foreach ($this->projectorClasses as $projectorClass) {
            $streams = $this->streamsForProjector($projectorClass);

            $eventEngine->watch(...$streams)
                ->with($projectorClass::projectionName(), $projectorClass, $projectorClass::version())
                ->filterEvents($projectorClass::events());
        }

        return $this;
    }

    /**
     * @param class-string<Projector> $projectorClass
     *
     * @return array<Stream>
     */
    private function streamsForProjector(string $projectorClass): array
    {
        /** @var array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> $aggregateRootClasses */
        $aggregateRootClasses = array_unique(
            array_map(
                fn ($eventClass) => $this->aggregateRootClassFromEventClass($eventClass),
                $projectorClass::events(),
            ),
        );

        return array_map(
            static fn ($aggregateRootClass) => Stream::ofLocalProjection(
                EventEngineUtil::fromAggregateClassToStreamName($aggregateRootClass),
            ),
            $aggregateRootClasses,
        );
    }

    /**
     * @param class-string<Event> $eventClass
     *
     * @return class-string<AggregateRoot<JsonSchemaAwareRecord>>
     */
    private function aggregateRootClassFromEventClass(string $eventClass): string
    {
        $commandAggregateMappings = $this->commandAggregateMapping();

        foreach ($this->commandEventMapping() as $commandClass => $eventClasses) {
            if (
                ! (
                array_key_exists($commandClass, $commandAggregateMappings)
                && in_array($eventClass, $eventClasses))
            ) {
                continue;
            }

            return $commandAggregateMappings[$commandClass];
        }

        throw new LogicException(sprintf('Unable to find aggregate for event %s', $eventClass));
    }

    private function registerDescriptions(EventEngine $eventEngine): self
    {
        foreach ($this->descriptionServices as $descriptionService) {
            $eventEngine->load($descriptionService);
        }

        return $this;
    }

    private function registerPreProcessorsAndAggregates(EventEngine $eventEngine): self
    {
        $usedAggregateRoots = [];
        $preProcessorMapping = $this->commandPreProcessorMapping();

        foreach ($this->commandAggregateMapping() as $commandClass => $aggregateRootClass) {
            $commandProcessor = $eventEngine->process($commandClass);

            if (array_key_exists($commandClass, $preProcessorMapping)) {
                $preProcessorClass = $preProcessorMapping[$commandClass];
                $commandProcessor->preProcess($preProcessorClass);
                unset($preProcessorMapping[$commandClass]);
            }

            $newAggregateRoot = $this->newAggregateRoot($aggregateRootClass, $commandClass, $usedAggregateRoots);

            $this
                ->handleCommand($commandProcessor, $aggregateRootClass, $commandClass, $newAggregateRoot)
                ->handleEvents($commandProcessor, $commandClass)
                ->handleServices($commandProcessor, $commandClass)
                ->handleStorage($commandProcessor, $aggregateRootClass, $newAggregateRoot);
        }

        foreach ($preProcessorMapping as $commandClass => $preProcessorClass) {
            $eventEngine
                ->process($commandClass)
                ->preProcess($preProcessorClass)
                ->withNew('test')
                ->handle([FlavourHint::class, 'useAggregate']);
        }

        return $this;
    }

    /**
     * @param class-string<AggregateRoot<JsonSchemaAwareRecord>> $aggregateRootClass
     * @param class-string<AggregateCommand> $commandClass
     * @param array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> $usedAggregateRoots
     */
    private function newAggregateRoot(
        string $aggregateRootClass,
        string $commandClass,
        array &$usedAggregateRoots,
    ): bool {
        if ($commandClass::__newAggregate()) {
            $usedAggregateRoots[] = $aggregateRootClass;
            $usedAggregateRoots = array_unique($usedAggregateRoots);

            return true;
        }

        $notFound = ! in_array($aggregateRootClass, $usedAggregateRoots);

        if ($notFound) {
            $usedAggregateRoots[] = $aggregateRootClass;
        }

        return $notFound;
    }

    private function handleCommand(
        CommandProcessorDescription $commandProcessor,
        string $aggregateRootClass,
        string $commandClass,
        bool $newAggregateRoot,
    ): self {
        $aggregateRootMethod = $newAggregateRoot ? 'withNew' : 'withExisting';

        $commandProcessor
            ->$aggregateRootMethod($aggregateRootClass)
            ->identifiedBy($this->aggregateIdentifierMapping()[$aggregateRootClass])
            ->handle($this->handle($aggregateRootClass, $commandClass, $newAggregateRoot));

        return $this;
    }

    /** @param class-string<AggregateCommand> $commandClass */
    private function handleEvents(
        CommandProcessorDescription $commandProcessor,
        string $commandClass,
    ): self {
        $events = $commandClass::__eventsToRecord();

        foreach ($events as $eventClass) {
            $commandProcessor
                ->recordThat($eventClass)
                ->apply([FlavourHint::class, 'useAggregate']);
        }

        return $this;
    }

    /** @param class-string<AggregateCommand> $commandClass */
    private function handleServices(
        CommandProcessorDescription $commandProcessor,
        string $commandClass,
    ): self {
        $services = $this->commandServiceMapping()[$commandClass] ?? [];

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

    /** @return array<string> */
    private function handle(string $aggregateRootClass, string $commandClass, bool $newAggregateRoot): array
    {
        if (! $newAggregateRoot) {
            return [FlavourHint::class, 'useAggregate'];
        }

        $handle = [$aggregateRootClass, $commandClass::__aggregateMethod()];

        if (is_callable($handle)) {
            return $handle;
        }

        throw new RuntimeException(
            sprintf(
                'Aggregate method \'%s\' for aggregate root \'%s\' is not callable.',
                $commandClass::__aggregateMethod(),
                $aggregateRootClass,
            ),
        );
    }

    /** @return array<class-string<AggregateCommand>, class-string<AggregateRoot<JsonSchemaAwareRecord>>> */
    private function commandAggregateMapping(): array
    {
        if (! empty($this->commandAggregateMapping)) {
            return $this->commandAggregateMapping;
        }

        foreach ($this->aggregateClasses as $aggregateClass) {
            $aggregateReflection = new ReflectionClass($aggregateClass);

            $publicAggregateMethods = $aggregateReflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($publicAggregateMethods as $publicAggregateMethod) {
                $parameters = $publicAggregateMethod->getParameters();
                $firstParameter = reset($parameters);

                if (! $firstParameter) {
                    continue;
                }

                /** @var ReflectionNamedType|ReflectionUnionType|null $commandTypes */
                $commandTypes = $firstParameter->getType();
                $commandTypes = $commandTypes instanceof ReflectionUnionType
                    ? $commandTypes->getTypes()
                    : [$commandTypes];

                $commandTypes = array_filter(
                    $commandTypes,
                    fn ($type) => $type !== null
                        && in_array($type->getName(), $this->commandClasses)
                );

                foreach ($commandTypes as $commandType) {
                    /** @var class-string<AggregateCommand> $commandClass */
                    $commandClass = $commandType->getName();

                    $this->commandAggregateMapping[$commandClass] = $aggregateClass;
                }
            }
        }

        return $this->commandAggregateMapping;
    }

    /** @return array<class-string<AggregateCommand>, array<class-string<Event>>> */
    private function commandEventMapping(): array
    {
        if (! empty($this->commandEventMapping)) {
            return $this->commandEventMapping;
        }

        foreach ($this->commandClasses as $commandClass) {
            /** @var class-string<AggregateCommand> $commandClass */
            $commandReflection = new ReflectionClass($commandClass);

            if (! $commandReflection->implementsInterface(AggregateCommand::class)) {
                continue;
            }

            $this->commandEventMapping[$commandClass] = $commandClass::__eventsToRecord();
        }

        return $this->commandEventMapping;
    }

    /** @return array<class-string<AggregateCommand>, array<class-string|string>> */
    private function commandServiceMapping(): array
    {
        if (! empty($this->commandServiceMapping)) {
            return $this->commandServiceMapping;
        }

        foreach ($this->aggregateClasses as $aggregateClass) {
            $aggregateReflection = new ReflectionClass($aggregateClass);

            $publicAggregateMethods = $aggregateReflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($publicAggregateMethods as $publicAggregateMethod) {
                $parameters = $publicAggregateMethod->getParameters();
                $firstParameter = array_shift($parameters);

                if (! $firstParameter) {
                    continue;
                }

                /** @var ReflectionNamedType|ReflectionUnionType|null $commandTypes */
                $commandTypes = $firstParameter->getType();
                $commandTypes = $commandTypes instanceof ReflectionUnionType
                    ? $commandTypes->getTypes()
                    : [$commandTypes];

                $commandTypes = array_filter(
                    $commandTypes,
                    fn ($type) => $type !== null
                        && in_array($type->getName(), $this->commandClasses)
                );

                /** @var array<class-string|string> $mapping */
                $mapping = array_map(
                    static function (ReflectionParameter $parameter) {
                        /** @var ReflectionNamedType|null $type */
                        $type = $parameter->getType();

                        return $type ? $type->getName() : null;
                    },
                    $parameters,
                );

                if (in_array(null, $mapping)) {
                    continue;
                }

                foreach ($commandTypes as $commandType) {
                    /** @var class-string<AggregateCommand> $commandClass */
                    $commandClass = $commandType->getName();
                    $mapping = $commandClass::__replaceServices($mapping);

                    $this->commandServiceMapping[$commandClass] = $mapping;
                }
            }
        }

        return $this->commandServiceMapping;
    }

    /** @return array<class-string<Command>, class-string<PreProcessor>> */
    private function commandPreProcessorMapping(): array
    {
        if (! empty($this->commandPreProcessorMapping)) {
            return $this->commandPreProcessorMapping;
        }

        foreach ($this->preProcessorClasses as $preProcessorClassOrInt => $preProcessorClassOrCommands) {
            /** @var class-string<PreProcessor> $preProcessorClass */
            $preProcessorClass = is_int($preProcessorClassOrInt)
                ? $preProcessorClassOrCommands
                : $preProcessorClassOrInt;

            /** @var array<class-string<Command>> $commandClasses */
            $commandClasses = is_int($preProcessorClassOrInt)
                ? []
                : $preProcessorClassOrCommands;

            $preProcessorReflection = new ReflectionClass($preProcessorClass);

            $invokeMethod = $preProcessorReflection->getMethod('__invoke');
            $invokeParameters = $invokeMethod->getParameters();

            $firstParameter = reset($invokeParameters);

            if (! $firstParameter) {
                throw new RuntimeException(
                    sprintf(
                        '__invoke method of preProcessor \'%s\' has no parameters.',
                        $preProcessorClass,
                    ),
                );
            }

            if (empty($commandClasses)) {
                /** @var ReflectionNamedType|ReflectionUnionType|null $commandType */
                $commandType = $firstParameter->getType();
                $commandTypes = $commandType instanceof ReflectionUnionType
                    ? $commandType->getTypes()
                    : [$commandType];

                foreach ($commandTypes as $commandType) {
                    if ($commandType === null || ! in_array($commandType->getName(), $this->commandClasses)) {
                        throw new RuntimeException(
                            sprintf(
                                'The first parameter of the __invoke method of preProcessor \'%s\' ' .
                                'has no type or is not a command.',
                                $preProcessorClass,
                            ),
                        );
                    }

                    $commandClasses[] = $commandType->getName();
                }
            }

            /** @var class-string<Command> $commandClass */
            foreach ($commandClasses as $commandClass) {
                $this->commandPreProcessorMapping[$commandClass] = $preProcessorClass;
            }
        }

        return $this->commandPreProcessorMapping;
    }

    /** @return array<class-string<AggregateRoot<JsonSchemaAwareRecord>>, string> */
    private function aggregateIdentifierMapping(): array
    {
        if (! empty($this->aggregateIdentifierMapping)) {
            return $this->aggregateIdentifierMapping;
        }

        foreach ($this->aggregateClasses as $aggregateClass) {
            $this->aggregateIdentifierMapping[$aggregateClass] = $aggregateClass::aggregateId();
        }

        return $this->aggregateIdentifierMapping;
    }

    /** @param class-string $message */
    private static function schemaFromMessage(string $message): PayloadSchema|TypeSchema
    {
        $reflectionClass = new ReflectionClass($message);

        if ($reflectionClass->implementsInterface(JsonSchemaAwareRecord::class)) {
            return $message::__schema();
        }

        if ($reflectionClass->implementsInterface(JsonSchemaAwareCollection::class)) {
            return JsonSchema::array($message::__itemSchema());
        }

        throw new RuntimeException(
            sprintf(
                'No schema found for message \'%s\'. Implement the JsonSchemaAwareRecord interface.',
                $message,
            ),
        );
    }

    private function mapEnvironment(string $environment): string
    {
        if (! isset(self::ENVIRONMENT_MAP[$environment])) {
            return $environment;
        }

        return self::ENVIRONMENT_MAP[$environment];
    }
}
