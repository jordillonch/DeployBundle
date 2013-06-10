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

//        $rootNode
//            ->children()
//            ->scalarNode('config')->defaultValue('')->end()
//            ->scalarNode('zones')->defaultValue('')->end()
//        ->end();

//        $rootNode
//            ->children()
//                ->arrayNode('config')
//                    ->info('Config definitions')
//                        ->requiresAtLeastOneElement()
//                        ->useAttributeAsKey('name')
//                        ->prototype('array')
//                    ->end()
//                ->end()
//                ->arrayNode('zones')
//                    ->info('Zones definitions')
//                        ->requiresAtLeastOneElement()
//                        ->useAttributeAsKey('name')
//                        ->prototype('array')
//                        ->children()
//                            ->scalarNode('deployer')
//                                ->isRequired()
//                            ->end()
//                            ->arrayNode('urls')
//                                ->isRequired()
//                            ->end()
//                        ->end()
//                    ->end()
//                ->end()
//            ->end()
//        ;


        return $treeBuilder;
    }
}
