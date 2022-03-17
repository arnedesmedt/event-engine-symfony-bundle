<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ads_event_engine');

        // @phpstan-ignore-next-line
        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('pdo_dsn')->end()
                ->scalarNode('pdo_dsn_lock')->end()
                ->scalarNode('domain_namespace')->defaultValue('App\Domain')->end()
                ->scalarNode('entity_namespace')->defaultValue('App\Domain\Entity')->end()
                ->arrayNode('document_store')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('id')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('schema')->defaultValue('VARCHAR(36) NOT NULL')->end()
                            ->end()
                        ->end()
                        ->booleanNode('transactional')->defaultValue(false)->end()
                        ->scalarNode('prefix')->defaultValue('')->end()
                    ->end()
                ->end()
                ->arrayNode('event_store')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('transactional')->defaultValue(false)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
