<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle;

use EventEngine\EventEngine;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Logger\SimpleMessageEngine;
use EventEngine\Persistence\MultiModelStore;
use EventEngine\Runtime\Flavour;
use EventEngine\Schema\PayloadSchema;
use EventEngine\Schema\TypeSchema;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;
use function sprintf;

final class Configurator
{
    private Flavour $flavour;
    private MultiModelStore $multiModelStore;
    private SimpleMessageEngine $simpleMessageEngine;
    private ContainerInterface $container;
    private string $environment;
    private bool $debug;
    /** @var array<class-string> */
    private array $descriptionServices;
    /** @var array<class-string> */
    private array $commandClasses;
    /** @var array<class-string> */
    private array $queryClasses;
    /** @var array<class-string> */
    private array $eventClasses;

    /**
     * @param array<class-string> $descriptionServices
     * @param array<class-string> $commandClasses
     * @param array<class-string> $queryClasses
     * @param array<class-string> $eventClasses
     */
    public function __construct(
        Flavour $flavour,
        MultiModelStore $multiModelStore,
        SimpleMessageEngine $simpleMessageEngine,
        ContainerInterface $container,
        string $environment,
        bool $debug,
        array $descriptionServices,
        array $commandClasses,
        array $queryClasses,
        array $eventClasses
    ) {
        $this->flavour = $flavour;
        $this->multiModelStore = $multiModelStore;
        $this->simpleMessageEngine = $simpleMessageEngine;
        $this->container = $container;
        $this->environment = $environment;
        $this->debug = $debug;
        $this->descriptionServices = $descriptionServices;
        $this->commandClasses = $commandClasses;
        $this->queryClasses = $queryClasses;
        $this->eventClasses = $eventClasses;
    }

    /**
     * @param class-string $message
     *
     * @return PayloadSchema|TypeSchema
     */
    private static function schemaFromMessage(string $message)
    {
        $reflectionClass = new ReflectionClass($message);

        if ($reflectionClass->implementsInterface(JsonSchemaAwareRecord::class)) {
            return $message::__schema();
        }

        throw new RuntimeException(
            sprintf(
                'No schema found for message \'%s\'. Implement the JsonSchemaAwareRecord interface.',
                $message
            )
        );
    }

    public function __invoke(EventEngine $eventEngine) : void
    {
        foreach ($this->commandClasses as $command) {
            /** @var PayloadSchema $schema */
            $schema = self::schemaFromMessage($command);
            $eventEngine->registerCommand($command, $schema);
        }

        foreach ($this->queryClasses as $query) {
            /** @var PayloadSchema $schema */
            $schema = self::schemaFromMessage($query);
            $eventEngine->registerQuery($query, $schema);
        }

        foreach ($this->eventClasses as $event) {
            /** @var PayloadSchema $schema */
            $schema = self::schemaFromMessage($event);
            $eventEngine->registerEvent($event, $schema);
        }

        foreach ($this->descriptionServices as $descriptionService) {
            $eventEngine->load($descriptionService);
        }

        $eventEngine->disableAutoProjecting();

        $eventEngine->initialize(
            $this->flavour,
            $this->multiModelStore,
            $this->simpleMessageEngine,
            $this->container
        )
            ->bootstrap(
                $this->environment,
                $this->debug
            );
    }
}
