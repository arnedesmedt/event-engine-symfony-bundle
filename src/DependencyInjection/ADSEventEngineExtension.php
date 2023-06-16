<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection;

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

use function array_merge;

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
    }

    public function prepend(ContainerBuilder $container): void
    {
        /** @var array<string, array<string, array<string, array<mixed>>>> $eventEngineConfig */
        $eventEngineConfig = $container->getExtensionConfig('ads_event_engine')[0] ?? [];

        $config = [
            'messenger' => [
                'default_bus' => 'command.bus',
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

        $container->prependExtensionConfig(
            'framework',
            $config,
        );
    }
}
