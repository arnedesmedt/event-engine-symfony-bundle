<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Classes;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Attribute\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Attribute\Command;
use ADS\Bundle\EventEngineBundle\Attribute\ControllerCommand;
use ADS\Bundle\EventEngineBundle\Attribute\Event;
use ADS\Bundle\EventEngineBundle\Attribute\Listener;
use ADS\Bundle\EventEngineBundle\Attribute\PreProcessor;
use ADS\Bundle\EventEngineBundle\Attribute\Projector;
use ADS\Bundle\EventEngineBundle\Attribute\Query;
use ADS\Bundle\EventEngineBundle\Attribute\Type;
use ADS\Bundle\EventEngineBundle\Repository\StateRepository;
use ADS\ValueObjects\Implementation\ListValue\IterableListValue;
use ADS\ValueObjects\ValueObject;
use EventEngine\EventEngineDescription;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use Iterator;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;

use function array_keys;
use function sprintf;

// phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
class ClassDivider
{
    /** @var Iterator<class-string, ReflectionClass<object>> */
    private readonly Iterator $reflectionClasses;

    /** @var array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    private array $commands;

    /** @var array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    private array $controllerCommands;

    /** @var array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    private array $aggregateCommands;

    /** @var array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    private array $queries;

    /** @var array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    private array $events;

    /** @var array<class-string<AggregateRoot<JsonSchemaAwareRecord>>, ReflectionClass<AggregateRoot<JsonSchemaAwareRecord>>> */
    private array $aggregates;

    /** @var array<class-string, ReflectionClass<object>> */
    private array $preProcessors;

    /** @var array<class-string, ReflectionClass<object>> */
    private array $listeners;

    /** @var array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    private array $types;

    /** @var array<class-string, ReflectionClass<object>> */
    private array $projectors;

    /** @var array<class-string<EventEngineDescription>, ReflectionClass<EventEngineDescription>> */
    private array $descriptions;

    /** @var array<class-string<StateRepository<IterableListValue<object>, JsonSchemaAwareRecord, ValueObject>>, ReflectionClass<StateRepository<IterableListValue<object>, JsonSchemaAwareRecord, ValueObject>>> */
    private array $repositories;

    /** @param array<string> $directories */
    public function __construct(readonly array $directories)
    {
        if ($directories === []) {
            throw new RuntimeException(
                sprintf(
                    'No directories configured for %s',
                    self::class,
                ),
            );
        }

        $this->reflectionClasses = ReflectionClassRecursiveIterator::fromDirectories($directories);
    }

    private function init(): void
    {
        $this->commands = [];
        $this->controllerCommands = [];
        $this->aggregateCommands = [];
        $this->queries = [];
        $this->events = [];
        $this->aggregates = [];
        $this->preProcessors = [];
        $this->listeners = [];
        $this->types = [];
        $this->projectors = [];
        $this->descriptions = [];
        $this->repositories = [];

        $this->reflectionClasses->rewind();

        foreach ($this->reflectionClasses as $className => $reflectionClass) {
            if (
                $this->addPossibleControllerCommand($className, $reflectionClass)
                || $this->addPossibleAggregateCommand($className, $reflectionClass)
                || $this->addPossibleCommand($className, $reflectionClass)
                || $this->addPossibleQuery($className, $reflectionClass)
                || $this->addPossibleEvent($className, $reflectionClass)
                || $this->addPossibleAggregate($className, $reflectionClass)
                || $this->addPossiblePreProcessor($className, $reflectionClass)
                || $this->addPossibleListener($className, $reflectionClass)
                || $this->addPossibleProjector($className, $reflectionClass)
                || $this->addPossibleType($className, $reflectionClass)
                || $this->addPossibleDescription($className, $reflectionClass)
            ) {
                continue;
            }

            $this->addPossibleRepository($className, $reflectionClass);
        }
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleControllerCommand(string $className, ReflectionClass $reflectionClass): bool
    {
        $isControllerCommand = $reflectionClass->getAttributes(
            ControllerCommand::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Command\ControllerCommand::class)
            && ! $reflectionClass->isAbstract();

        if ($isControllerCommand) {
            $this->isJsonSchemaAwareRecord($className, $reflectionClass, 'controller command');
            /** @var class-string<JsonSchemaAwareRecord> $className */
            /** @var ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
            $this->controllerCommands[$className] = $reflectionClass;
            $this->commands[$className] = $reflectionClass;
        }

        return $isControllerCommand;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleAggregateCommand(string $className, ReflectionClass $reflectionClass): bool
    {
        $isAggregateCommand = $reflectionClass->getAttributes(
            AggregateCommand::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Command\AggregateCommand::class)
            && ! $reflectionClass->isAbstract();

        if ($isAggregateCommand) {
            $this->isJsonSchemaAwareRecord($className, $reflectionClass, 'aggregate command');
            /** @var class-string<JsonSchemaAwareRecord> $className */
            /** @var ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
            $this->aggregateCommands[$className] = $reflectionClass;
            $this->commands[$className] = $reflectionClass;
        }

        return $isAggregateCommand;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleCommand(string $className, ReflectionClass $reflectionClass): bool
    {
        $isCommand = $reflectionClass->getAttributes(
            Command::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Command\Command::class)
            && ! $reflectionClass->isAbstract();

        if ($isCommand) {
            $this->isJsonSchemaAwareRecord($className, $reflectionClass, 'command');
            /** @var class-string<JsonSchemaAwareRecord> $className */
            /** @var ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
            $this->commands[$className] = $reflectionClass;
        }

        return $isCommand;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleQuery(string $className, ReflectionClass $reflectionClass): bool
    {
        $isQuery = $reflectionClass->getAttributes(
            Query::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Query\Query::class)
            && ! $reflectionClass->isAbstract();

        if ($isQuery) {
            $this->isJsonSchemaAwareRecord($className, $reflectionClass, 'query');
            /** @var class-string<JsonSchemaAwareRecord> $className */
            /** @var ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
            $this->queries[$className] = $reflectionClass;
        }

        return $isQuery;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleEvent(string $className, ReflectionClass $reflectionClass): bool
    {
        $isEvent = $reflectionClass->getAttributes(
            Event::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Event\Event::class)
            && ! $reflectionClass->isAbstract();

        if ($isEvent) {
            $this->isJsonSchemaAwareRecord($className, $reflectionClass, 'event');
            /** @var class-string<JsonSchemaAwareRecord> $className */
            /** @var ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
            $this->events[$className] = $reflectionClass;
        }

        return $isEvent;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleAggregate(string $className, ReflectionClass $reflectionClass): bool
    {
        $isAggregate = $reflectionClass->implementsInterface(AggregateRoot::class)
            && ! $reflectionClass->isAbstract();

        if ($isAggregate) {
            /** @var class-string<AggregateRoot<JsonSchemaAwareRecord>> $className */
            /** @var ReflectionClass<AggregateRoot<JsonSchemaAwareRecord>> $reflectionClass */
            $this->aggregates[$className] = $reflectionClass;
        }

        return $isAggregate;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossiblePreProcessor(string $className, ReflectionClass $reflectionClass): bool
    {
        $isPreProcessor = $reflectionClass->getAttributes(
            PreProcessor::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\PreProcessor\PreProcessor::class)
            && ! $reflectionClass->isAbstract();

        if ($isPreProcessor) {
            $this->preProcessors[$className] = $reflectionClass;
        }

        return $isPreProcessor;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleListener(string $className, ReflectionClass $reflectionClass): bool
    {
        $isListener = $reflectionClass->getAttributes(
            Listener::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Event\Listener::class)
            && ! $reflectionClass->isAbstract();

        if ($isListener) {
            $this->listeners[$className] = $reflectionClass;
        }

        return $isListener;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleProjector(string $className, ReflectionClass $reflectionClass): bool
    {
        $isProjector = $reflectionClass->getAttributes(
            Projector::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Projector\Projector::class)
            && ! $reflectionClass->isAbstract();

        if ($isProjector) {
            $this->projectors[$className] = $reflectionClass;
        }

        return $isProjector;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleType(string $className, ReflectionClass $reflectionClass): bool
    {
        $isType = $reflectionClass->getAttributes(
            Type::class,
            ReflectionAttribute::IS_INSTANCEOF,
        ) || $reflectionClass->implementsInterface(\ADS\Bundle\EventEngineBundle\Type\Type::class)
            && ! $reflectionClass->isAbstract();

        if ($isType) {
            $this->isJsonSchemaAwareRecord($className, $reflectionClass, 'type');
            /** @var class-string<JsonSchemaAwareRecord> $className */
            /** @var ReflectionClass<JsonSchemaAwareRecord> $reflectionClass */
            $this->types[$className] = $reflectionClass;
        }

        return $isType;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleDescription(string $className, ReflectionClass $reflectionClass): bool
    {
        $isDescription = $reflectionClass->implementsInterface(EventEngineDescription::class)
            && ! $reflectionClass->isAbstract();

        if ($isDescription) {
            /** @var class-string<EventEngineDescription> $className */
            /** @var ReflectionClass<EventEngineDescription> $reflectionClass */
            $this->descriptions[$className] = $reflectionClass;
        }

        return $isDescription;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function addPossibleRepository(string $className, ReflectionClass $reflectionClass): bool
    {
        $isRepository = $reflectionClass->implementsInterface(StateRepository::class)
            && ! $reflectionClass->isAbstract();

        if ($isRepository) {
            /** @var class-string<StateRepository<IterableListValue<object>, JsonSchemaAwareRecord, ValueObject>> $className */
            /** @var ReflectionClass<StateRepository<IterableListValue<object>, JsonSchemaAwareRecord, ValueObject>> $reflectionClass */
            $this->repositories[$className] = $reflectionClass;
        }

        return $isRepository;
    }

    /**
     * @param class-string $className
     * @param ReflectionClass<object> $reflectionClass
     */
    private function isJsonSchemaAwareRecord(string $className, ReflectionClass $reflectionClass, string $type): void
    {
        if ($reflectionClass->implementsInterface(JsonSchemaAwareRecord::class)) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Class %s is not a JsonSchemaAwareRecord, but should be used as %s',
                $className,
                $type,
            ),
        );
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    public function commands(): array
    {
        if (! isset($this->commands)) {
            $this->init();
        }

        return $this->commands;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function commandClasses(): array
    {
        return array_keys($this->commands());
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    public function controllerCommands(): array
    {
        if (! isset($this->controllerCommands)) {
            $this->init();
        }

        return $this->controllerCommands;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function controllerCommandClasses(): array
    {
        return array_keys($this->controllerCommands());
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    public function aggregateCommands(): array
    {
        if (! isset($this->aggregateCommands)) {
            $this->init();
        }

        return $this->aggregateCommands;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function aggregateCommandClasses(): array
    {
        return array_keys($this->aggregateCommands());
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    public function queries(): array
    {
        if (! isset($this->queries)) {
            $this->init();
        }

        return $this->queries;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function queryClasses(): array
    {
        return array_keys($this->queries());
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    public function events(): array
    {
        if (! isset($this->events)) {
            $this->init();
        }

        return $this->events;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function eventClasses(): array
    {
        return array_keys($this->events());
    }

    /** @return array<class-string<AggregateRoot<JsonSchemaAwareRecord>>, ReflectionClass<AggregateRoot<JsonSchemaAwareRecord>>> */
    public function aggregates(): array
    {
        if (! isset($this->aggregates)) {
            $this->init();
        }

        return $this->aggregates;
    }

    /** @return array<class-string<AggregateRoot<JsonSchemaAwareRecord>>> */
    public function aggregateClasses(): array
    {
        return array_keys($this->aggregates());
    }

    /** @return array<class-string, ReflectionClass<object>> */
    public function preProcessors(): array
    {
        if (! isset($this->preProcessors)) {
            $this->init();
        }

        return $this->preProcessors;
    }

    /** @return array<class-string> */
    public function preProcessorClasses(): array
    {
        return array_keys($this->preProcessors());
    }

    /** @return array<class-string, ReflectionClass<object>> */
    public function listeners(): array
    {
        if (! isset($this->listeners)) {
            $this->init();
        }

        return $this->listeners;
    }

    /** @return array<class-string> */
    public function listenerClasses(): array
    {
        return array_keys($this->listeners());
    }

    /** @return array<class-string, ReflectionClass<object>> */
    public function projectors(): array
    {
        if (! isset($this->projectors)) {
            $this->init();
        }

        return $this->projectors;
    }

    /** @return array<class-string> */
    public function projectorClasses(): array
    {
        return array_keys($this->projectors());
    }

    /** @return array<class-string<JsonSchemaAwareRecord>, ReflectionClass<JsonSchemaAwareRecord>> */
    public function types(): array
    {
        if (! isset($this->types)) {
            $this->init();
        }

        return $this->types;
    }

    /** @return array<class-string<JsonSchemaAwareRecord>> */
    public function typeClasses(): array
    {
        return array_keys($this->types());
    }

    /** @return array<class-string<EventEngineDescription>, ReflectionClass<EventEngineDescription>> */
    public function descriptions(): array
    {
        if (! isset($this->descriptions)) {
            $this->init();
        }

        return $this->descriptions;
    }

    /** @return array<class-string<EventEngineDescription>> */
    public function descriptionClasses(): array
    {
        return array_keys($this->descriptions());
    }

    /** @return array<class-string<StateRepository<IterableListValue<object>, JsonSchemaAwareRecord, ValueObject>>, ReflectionClass<StateRepository<IterableListValue<object>, JsonSchemaAwareRecord, ValueObject>>> */
    public function repositories(): array
    {
        if (! isset($this->repositories)) {
            $this->init();
        }

        return $this->repositories;
    }

    /** @return array<class-string<StateRepository<IterableListValue<object>, JsonSchemaAwareRecord, ValueObject>>> */
    public function repositoryClasses(): array
    {
        return array_keys($this->repositories());
    }
}
