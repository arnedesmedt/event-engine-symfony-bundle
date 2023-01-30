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
                ->scalarNode('pdo_dsn')->defaultValue('%env(string:PDO_DSN)%')->end()
                ->scalarNode('entity_namespace')->defaultValue('App\Domain\Entity')->end()
                ->scalarNode('seed_path')->defaultValue('database/seeds')->end()
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
                ->arrayNode('messenger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('command')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('transport')
                                    ->defaultValue('doctrine://default?queue_name=event_engine_command')
                                ->end()
                                ->scalarNode('retry')
                                    ->defaultValue('ADS\Bundle\EventEngineBundle\Messenger\Retry\CommandRetry')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('event')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('transport')
                                    ->defaultValue('doctrine://default?queue_name=event_engine_event')
                                ->end()
                                ->scalarNode('retry')
                                    ->defaultValue('ADS\Bundle\EventEngineBundle\Messenger\Retry\EventRetry')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('query')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('transport')
                                    ->defaultValue('doctrine://default?queue_name=event_engine_query')
                                ->end()
                                ->scalarNode('retry')
                                    ->defaultValue('ADS\Bundle\EventEngineBundle\Messenger\Retry\QueryRetry')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
