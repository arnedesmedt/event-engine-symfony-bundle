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

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('domain_namespace')->defaultValue('App\Domain')->end()
                ->scalarNode('entity_namespace')->defaultValue('App\Domain\Entity')->end()
                ->arrayNode('document_store')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('id')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('schema')->defaultValue('CHAR(36) NOT NULL')->end()
                            ->end()
                        ->end()
                        ->booleanNode('transactional')->defaultValue(false)->end()
                        ->scalarNode('prefix')->defaultValue('')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
