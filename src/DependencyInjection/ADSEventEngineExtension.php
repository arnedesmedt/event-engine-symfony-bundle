<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection;

use ADS\Bundle\EventEngineBundle\Messenger\Message\CommandMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Message\EventMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Message\QueryMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Middleware\DontSendToFailureTransportMiddleware;
use ADS\Bundle\EventEngineBundle\Messenger\Middleware\PickTransportMiddleware;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\CommandRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\EventRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\QueryRetry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class ADSEventEngineExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    /**
     * phpcs:ignore Generic.Files.LineLength.TooLong
     * @param array{"event_store": array{"transactional": bool}, "messenger": array<string, array<string, string>>, "document_store": array{"prefix": string, "id": array{"schema": string}, "transactional": bool}, "entity_namespace": string, "pdo_dsn": string, "seed_path": string} $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config'),
        );

        $loader->load('event_engine_symfony.yaml');

        $container->setParameter(
            'event_engine.document_store.prefix',
            $mergedConfig['document_store']['prefix'],
        );

        $container->setParameter(
            'event_engine.document_store.id.schema',
            $mergedConfig['document_store']['id']['schema'],
        );

        $container->setParameter(
            'event_engine.document_store.transactional',
            $mergedConfig['document_store']['transactional'],
        );

        $container->setParameter(
            'event_engine.event_store.transactional',
            $mergedConfig['event_store']['transactional'],
        );

        $container->setParameter(
            'event_engine.entity_namespace',
            $mergedConfig['entity_namespace'],
        );

        $container->setParameter(
            'event_engine.pdo_dsn',
            $mergedConfig['pdo_dsn'],
        );

        $container->setParameter(
            'event_engine.seed_path',
            $mergedConfig['seed_path'],
        );

        $container->setParameter(
            'event_engine.messenger.command.transport',
            $mergedConfig['messenger']['command']['transport'],
        );

        $container->setParameter(
            'event_engine.messenger.command.retry',
            $mergedConfig['messenger']['command']['retry'],
        );

        $container->setParameter(
            'event_engine.messenger.event.transport',
            $mergedConfig['messenger']['event']['transport'],
        );

        $container->setParameter(
            'event_engine.messenger.event.retry',
            $mergedConfig['messenger']['event']['retry'],
        );

        $container->setParameter(
            'event_engine.messenger.query.transport',
            $mergedConfig['messenger']['query']['transport'],
        );

        $container->setParameter(
            'event_engine.messenger.query.retry',
            $mergedConfig['messenger']['query']['retry'],
        );
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $resolvingBag = $container->getParameterBag();
        $configs = $resolvingBag->resolveValue($configs);

        //phpcs:disable Squiz.Arrays.ArrayDeclaration.MultiLineNotAllowed
        $config = [
            'messenger' => [
                'default_bus' => 'command.bus',
                'buses' => [
                    // deprecated
                    'command.bus' => [
                        'middleware' => [
                            DontSendToFailureTransportMiddleware::class,
                        ],
                    ],
                    // deprecated
                    'event.bus' => [
                        'middleware' => [
                            DontSendToFailureTransportMiddleware::class,
                        ],
                    ],
                    // deprecated
                    'query.bus' => [
                        'middleware' => [
                            DontSendToFailureTransportMiddleware::class,
                        ],
                    ],
                    'command' => [
                        'middleware' => [
                            PickTransportMiddleware::class,
                            DontSendToFailureTransportMiddleware::class,
                        ],
                    ],
                    'event' => [
                        'middleware' => [
                            PickTransportMiddleware::class,
                            DontSendToFailureTransportMiddleware::class,
                        ],
                    ],
                    'query' => [
                        'middleware' => [
                            PickTransportMiddleware::class,
                            DontSendToFailureTransportMiddleware::class,
                        ],
                    ],
                ],
                'transports' => [
                    // deprecated
                    'event_engine.command' => [
                        'dsn' => $configs['event_engine.messenger.command.transport']
                            ?? 'doctrine://default?queue_name=event_engine_command',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.command.retry']
                                ?? CommandRetry::class,
                        ],
                        'failure_transport' => 'failed.event_engine.command',
                    ],
                    // deprecated
                    'failed.event_engine.command' => [
                        'dsn' => 'doctrine://default?queue_name=failed_event_engine_command',
                    ],
                    // deprecated
                    'event_engine.event' => [
                        'dsn' => $configs['event_engine.messenger.event.transport']
                            ?? 'doctrine://default?queue_name=event_engine_event',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.event.retry']
                                ?? EventRetry::class,
                        ],
                        'failure_transport' => 'failed.event_engine.event',
                    ],
                    // deprecated
                    'failed.event_engine.event' => [
                        'dsn' => 'doctrine://default?queue_name=failed_event_engine_event',
                    ],
                    // deprecated
                    'event_engine.query' => [
                        'dsn' => $configs['event_engine.messenger.query.transport']
                            ?? 'doctrine://default?queue_name=event_engine_query',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.query.retry']
                                ?? QueryRetry::class,
                        ],
                        'failure_transport' => 'failed.event_engine.query',
                    ],
                    // deprecated
                    'failed.event_engine.query' => [
                        'dsn' => 'doctrine://default?queue_name=failed_event_engine_query',
                    ],
                    'command' => [
                        'dsn' => $configs['event_engine.messenger.command.transport']
                            ?? 'doctrine://default?table_name=messenger_commands',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.command.retry']
                                ?? CommandRetry::class,
                        ],
                        'failure_transport' => 'command.failed',
                        'options' => [
                            'pgsql_get_notify' => true,
                            'pgsql_get_notify_timeout' => 500,
                        ],
                    ],
                    'command.failed' => [
                        'dsn' => 'doctrine://default?table_name=messenger_commands&queue_name=failed',
                    ],
                    'event' => [
                        'dsn' => $configs['event_engine.messenger.event.transport']
                            ?? 'doctrine://default?table_name=messenger_events',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.event.retry']
                                ?? EventRetry::class,
                        ],
                        'failure_transport' => 'event.failed',
                        'options' => [
                            'pgsql_get_notify' => true,
                            'pgsql_get_notify_timeout' => 500,
                        ],
                    ],
                    'event.failed' => [
                        'dsn' => 'doctrine://default?table_name=messenger_events&queue_name=failed',
                    ],
                    'query' => [
                        'dsn' => $configs['event_engine.messenger.query.transport']
                            ?? 'doctrine://default?table_name=messenger_queries',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.query.retry']
                                ?? QueryRetry::class,
                        ],
                        'failure_transport' => 'query.failed',
                        'options' => [
                            'pgsql_get_notify' => true,
                            'pgsql_get_notify_timeout' => 500,
                        ],
                    ],
                    'query.failed' => [
                        'dsn' => 'doctrine://default?table_name=messenger_queries&queue_name=failed',
                    ],
                ],
                // deprecated
                'routing' => [
                    CommandMessageWrapper::class => 'event_engine.command',
                    EventMessageWrapper::class => 'event_engine.event',
                    QueryMessageWrapper::class => 'event_engine.query',
                ],
            ],
        ];
        //phpcs:enable Squiz.Arrays.ArrayDeclaration.MultiLineNotAllowed

        $container->prependExtensionConfig(
            'framework',
            $config,
        );
    }
}
