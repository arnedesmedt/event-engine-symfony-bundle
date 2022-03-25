<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection;

use ADS\Bundle\EventEngineBundle\Messenger\Message\CommandMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Message\EventMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Message\QueryMessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\CommandRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\EventRetry;
use ADS\Bundle\EventEngineBundle\Messenger\Retry\QueueRetry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class ADSEventEngineExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    /**
     * phpcs:ignore Generic.Files.LineLength.TooLong
     * @param array{"event_store": array{"transactional": bool}, "messenger": array<string, string>, "document_store": array{"prefix": string, "id": array{"schema": string}, "transactional": bool}, "entity_namespace": string, "domain_namespace": string, "pdo_dsn": string} $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('event_engine.yaml');

        $container->setParameter(
            'event_engine.document_store.prefix',
            $mergedConfig['document_store']['prefix']
        );

        $container->setParameter(
            'event_engine.document_store.id.schema',
            $mergedConfig['document_store']['id']['schema']
        );

        $container->setParameter(
            'event_engine.document_store.transactional',
            $mergedConfig['document_store']['transactional']
        );

        $container->setParameter(
            'event_engine.event_store.transactional',
            $mergedConfig['event_store']['transactional']
        );

        $container->setParameter(
            'event_engine.entity_namespace',
            $mergedConfig['entity_namespace']
        );

        $container->setParameter(
            'event_engine.domain_namespace',
            $mergedConfig['domain_namespace']
        );

        $container->setParameter(
            'event_engine.pdo_dsn',
            $mergedConfig['pdo_dsn']
        );

        $container->setParameter(
            'event_engine.messenger.async.transport.command',
            $mergedConfig['messenger']['command_async_transport']
        );

        $container->setParameter(
            'event_engine.messenger.async.transport.event',
            $mergedConfig['messenger']['event_async_transport']
        );

        $container->setParameter(
            'event_engine.messenger.async.transport.query',
            $mergedConfig['messenger']['query_async_transport']
        );

        $container->setParameter(
            'event_engine.messenger.async.transport.command.retry',
            $mergedConfig['messenger']['command_async_retry']
        );

        $container->setParameter(
            'event_engine.messenger.async.transport.event.retry',
            $mergedConfig['messenger']['event_async_retry']
        );

        $container->setParameter(
            'event_engine.messenger.async.transport.query.retry',
            $mergedConfig['messenger']['query_async_retry']
        );
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $resolvingBag = $container->getParameterBag();
        $configs = $resolvingBag->resolveValue($configs);

        $config = [
            'messenger' => [
                'default_bus' => 'command.bus',
                'buses' => [
                    'command.bus' => [],
                    'event.bus' => [],
                    'query.bus' => [],
                ],
                'transports' => [
                    'command.async' => [
                        'dsn' => $configs['event_engine.messenger.async.transport.command']
                            ?? 'doctrine://default?queue_name=event_engine_command',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.async.transport.command.retry']
                                ?? CommandRetry::class,
                        ],
                    ],
                    'event.async' => [
                        'dsn' => $configs['event_engine.messenger.async.transport.event']
                            ?? 'doctrine://default?queue_name=event_engine_event',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.async.transport.event.retry']
                                ?? EventRetry::class,
                        ],
                    ],
                    'query.async' => [
                        'dsn' => $configs['event_engine.messenger.async.transport.query']
                            ?? 'doctrine://default?queue_name=event_engine_query',
                        'retry_strategy' => [
                            'service' => $configs['event_engine.messenger.async.transport.query.retry']
                                ?? QueueRetry::class,
                        ],
                    ],
                ],
                'routing' => [
                    CommandMessageWrapper::class => 'command.async',
                    EventMessageWrapper::class => 'event.async',
                    QueryMessageWrapper::class => 'query.async',
                ],
            ],
        ];

        if (isset($configs['event_engine.messenger.async.transport.command.retry'])) {
            $config['messenger']['transports']['command.async']['retry_strategy']['service'] =
                $configs['event_engine.messenger.async.transport.command.retry'];
        }

        if (isset($configs['event_engine.messenger.async.transport.event.retry'])) {
            $config['messenger']['transports']['event.async']['retry_strategy']['service'] =
                $configs['event_engine.messenger.async.transport.event.retry'];
        }

        if (isset($configs['event_engine.messenger.async.transport.query.retry'])) {
            $config['messenger']['transports']['query.async']['retry_strategy']['service'] =
                $configs['event_engine.messenger.async.transport.query.retry'];
        }

        $container->prependExtensionConfig(
            'framework',
            $config
        );
    }
}
