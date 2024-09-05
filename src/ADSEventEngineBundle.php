<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle;

use ADS\Bundle\EventEngineBundle\Classes\ClassDivider;
use ADS\Bundle\EventEngineBundle\DependencyInjection\Compiler\EventEnginePass;
use ADS\Bundle\EventEngineBundle\Messenger\Middleware\DontSendToFailureTransportMiddleware;
use ADS\Bundle\EventEngineBundle\Messenger\Middleware\PickTransportMiddleware;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\CommandRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\EventRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\QueryRetry;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function array_merge;

final class ADSEventEngineBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EventEnginePass());
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();
        $configs = $rootNode->children();
        $configs->arrayNode('directories')->prototype('scalar');
        $configs->scalarNode('pdo_dsn')->defaultValue('%env(string:PDO_DSN)%');
        $configs->scalarNode('entity_namespace')->defaultValue('App\Domain\Entity');
        $configs->scalarNode('seed_path')->defaultValue('database/seeds');

        $documentStore = $configs->arrayNode('document_store')->addDefaultsIfNotSet()->children();
        $documentStoreId = $documentStore->arrayNode('id')->addDefaultsIfNotSet()->children();
        $documentStoreId->scalarNode('schema')->defaultValue('VARCHAR(36) NOT NULL');
        $documentStore->booleanNode('transactional')->defaultValue(false);
        $documentStore->scalarNode('prefix')->defaultValue('');

        $eventStore = $configs->arrayNode('event_store')->addDefaultsIfNotSet()->children();
        $eventStore->booleanNode('transactional')->defaultValue(false);

        $messenger = $configs->arrayNode('messenger')->addDefaultsIfNotSet()->children();

        $command = $messenger->arrayNode('command')->addDefaultsIfNotSet()->children();
        $command->scalarNode('transport')->defaultValue('doctrine://default?table_name=messenger_commands');
        $command->scalarNode('retry')->defaultValue(CommandRetry::class);
        $command->arrayNode('middleware')->prototype('scalar');

        $command = $messenger->arrayNode('command_low_priority')->addDefaultsIfNotSet()->children();
        $command->scalarNode('transport')->defaultValue('doctrine://default?table_name=messenger_commands');
        $command->scalarNode('retry')->defaultValue(CommandRetry::class);
        $command->arrayNode('middleware')->prototype('scalar');

        $event = $messenger->arrayNode('event')->addDefaultsIfNotSet()->children();
        $event->scalarNode('transport')->defaultValue('doctrine://default?table_name=messenger_events');
        $event->scalarNode('retry')->defaultValue(EventRetry::class);
        $event->arrayNode('middleware')->prototype('scalar');

        $query = $messenger->arrayNode('query')->addDefaultsIfNotSet()->children();
        $query->scalarNode('transport')->defaultValue('doctrine://default?table_name=messenger_queries');
        $query->scalarNode('retry')->defaultValue(QueryRetry::class);
        $query->arrayNode('middleware')->prototype('scalar');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array<string, array<string, array<string, array<mixed>>>> $eventEngineConfig */
        $eventEngineConfig = $builder->getExtensionConfig('ads_event_engine')[0] ?? [];

        $config = [
            'messenger' => [
                'default_bus' => 'command',
                'buses' => [
                    'command' => [
                        'middleware' => array_merge(
                            [
                                PickTransportMiddleware::class,
                                DontSendToFailureTransportMiddleware::class,
                            ],
                            $eventEngineConfig['messenger']['command']['middleware'] ?? [],
                        ),
                    ],
                    'command_low_priority' => [
                        'middleware' => array_merge(
                            [
                                PickTransportMiddleware::class,
                                DontSendToFailureTransportMiddleware::class,
                            ],
                            $eventEngineConfig['messenger']['command_low_priority']['middleware'] ?? [],
                        ),
                    ],
                    'event' => [
                        'middleware' => array_merge(
                            [
                                PickTransportMiddleware::class,
                                DontSendToFailureTransportMiddleware::class,
                            ],
                            $eventEngineConfig['messenger']['event']['middleware'] ?? [],
                        ),
                    ],
                    'query' => [
                        'middleware' => array_merge(
                            [
                                PickTransportMiddleware::class,
                                DontSendToFailureTransportMiddleware::class,
                            ],
                            $eventEngineConfig['messenger']['query']['middleware'] ?? [],
                        ),
                    ],
                ],
                'transports' => [
                    'command' => [
                        'dsn' => $eventEngineConfig['messenger']['command']['transport']
                            ?? 'doctrine://default?table_name=messenger_commands',
                        'retry_strategy' => [
                            'service' => $eventEngineConfig['messenger']['command']['retry'] ?? CommandRetry::class,
                        ],
                        'failure_transport' => 'command.failed',
                        'options' => [
                            'use_notify' => true,
                            'check_delayed_interval' => 0,
                        ],
                    ],
                    'command_low_priority' => [
                        'dsn' => $eventEngineConfig['messenger']['command_low_priority']['transport']
                            ?? 'doctrine://default?table_name=messenger_commands&queue_name=low-priority',
                        'retry_strategy' => [
                            'service' => $eventEngineConfig['messenger']['command_low_priority']['retry']
                                ?? CommandRetry::class,
                        ],
                        'failure_transport' => 'command.failed',
                        'options' => [
                            'use_notify' => true,
                            'check_delayed_interval' => 0,
                        ],
                    ],
                    'command.failed' => ['dsn' => 'doctrine://default?table_name=messenger_commands&queue_name=failed'],
                    'event' => [
                        'dsn' => $eventEngineConfig['messenger']['event']['transport']
                            ?? 'doctrine://default?table_name=messenger_events',
                        'retry_strategy' => [
                            'service' => $eventEngineConfig['messenger']['event']['retry'] ?? EventRetry::class,
                        ],
                        'failure_transport' => 'event.failed',
                        'options' => [
                            'use_notify' => true,
                            'check_delayed_interval' => 0,
                        ],
                    ],
                    'event.failed' => ['dsn' => 'doctrine://default?table_name=messenger_events&queue_name=failed'],
                    'query' => [
                        'dsn' => $eventEngineConfig['messenger']['query']['transport']
                            ?? 'doctrine://default?table_name=messenger_queries',
                        'retry_strategy' => [
                            'service' => $eventEngineConfig['messenger']['query']['retry'] ?? QueryRetry::class,
                        ],
                        'failure_transport' => 'query.failed',
                        'options' => [
                            'use_notify' => true,
                            'check_delayed_interval' => 0,
                        ],
                    ],
                    'query.failed' => ['dsn' => 'doctrine://default?table_name=messenger_queries&queue_name=failed'],
                ],
            ],
        ];

        $builder->prependExtensionConfig(
            'framework',
            $config,
        );
    }

    /**
     * phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @param array{"event_store": array{"transactional": bool}, "messenger": array<string, array<string, string>>, "document_store": array{"prefix": string, "id": array{"schema": string}, "transactional": bool}, "entity_namespace": string, "pdo_dsn": string, "seed_path": string, "directories": array<string>} $config
     *
     * phpcs:enable Generic.Files.LineLength.TooLong
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $classContainer = new ClassDivider($config['directories']);

        $builder->setParameter('event_engine.commands', $classContainer->commandClasses());
        $builder->setParameter('event_engine.controller_commands', $classContainer->controllerCommandClasses());
        $builder->setParameter('event_engine.aggregate_commands', $classContainer->aggregateCommandClasses());
        $builder->setParameter('event_engine.queries', $classContainer->queryClasses());
        $builder->setParameter('event_engine.events', $classContainer->eventClasses());
        $builder->setParameter('event_engine.aggregates', $classContainer->aggregateClasses());
        $builder->setParameter('event_engine.pre_processors', $classContainer->preProcessorClasses());
        $builder->setParameter('event_engine.listeners', $classContainer->listenerClasses());
        $builder->setParameter('event_engine.projectors', $classContainer->projectorClasses());
        $builder->setParameter('event_engine.types', $classContainer->typeClasses());
        $builder->setParameter('event_engine.descriptions', $classContainer->descriptionClasses());
        $builder->setParameter('event_engine.repositories', $classContainer->repositoryClasses());

        $builder->setParameter('event_engine.document_store.prefix', $config['document_store']['prefix']);
        $builder->setParameter('event_engine.document_store.id.schema', $config['document_store']['id']['schema']);
        $builder->setParameter('event_engine.document_store.transactional', $config['document_store']['transactional']);
        $builder->setParameter('event_engine.event_store.transactional', $config['event_store']['transactional']);
        $builder->setParameter('event_engine.entity_namespace', $config['entity_namespace']);
        $builder->setParameter('event_engine.pdo_dsn', $config['pdo_dsn']);
        $builder->setParameter('event_engine.seed_path', $config['seed_path']);
        $builder->setParameter('event_engine.directories', $config['directories']);

        $loader = new YamlFileLoader(
            $builder,
            new FileLocator(__DIR__ . '/Resources/config'),
        );

        $loader->load('event_engine_symfony.yaml');
    }
}
