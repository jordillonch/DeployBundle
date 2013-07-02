<?php

namespace JordiLlonch\Bundle\DeployBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jordi_llonch_deploy');

        $rootNode
            ->children()
                ->arrayNode('config')
                    ->info('Base config')
                        ->isRequired()
                        ->children()
                            ->scalarNode('project')->isRequired()
                            ->end()
                            ->scalarNode('mail_from')
                            ->end()
                            ->arrayNode('mail_to')
                                ->requiresAtLeastOneElement()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->scalarNode('local_repository_dir')->isRequired()
                            ->end()
                            ->scalarNode('clean_before_days')
                            ->end()
                            ->scalarNode('sudo')
                            ->end()
                        ->end()
                    ->end()
                ->arrayNode('zones')
                    ->info('Zones config')
                        ->requiresAtLeastOneElement()
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                        ->children()
                            ->scalarNode('project')
                            ->end()
                            ->scalarNode('mail_from')
                            ->end()
                            ->arrayNode('mail_to')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->scalarNode('local_repository_dir')
                            ->end()
                            ->scalarNode('clean_before_days')
                            ->end()
                            ->scalarNode('sudo')
                            ->end()
                            ->scalarNode('deployer')->isRequired()
                            ->end()
                            ->scalarNode('environment')->isRequired()
                            ->end()
                            ->arrayNode('urls')
                                ->requiresAtLeastOneElement()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->scalarNode('checkout_url')->isRequired()
                            ->end()
                            ->scalarNode('checkout_branch')->isRequired()
                            ->end()
                            ->scalarNode('checkout_proxy')
                            ->end()
                            ->scalarNode('repository_dir')->isRequired()
                            ->end()
                            ->scalarNode('production_dir')->isRequired()
                            ->end()
                            ->arrayNode('custom')
                                ->prototype('variable')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
