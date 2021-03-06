<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class ADSEventEngineExtension extends ConfigurableExtension
{
    /**
     * @param array<mixed> $mergedConfig
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
    }
}
