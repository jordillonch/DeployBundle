<?php


/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
                            ->enumNode('vcs')
                                ->values(array('', 'git'))
                            ->end()
                            ->scalarNode('servers_parameter_file')->isRequired()
                            ->end()
                            ->scalarNode('local_repository_dir')->isRequired()
                            ->end()
                            ->scalarNode('clean_max_deploys')
                            ->end()
                            ->scalarNode('sudo')
                            ->end()
                            ->arrayNode('helper')
                                ->prototype('variable')
                                ->end()
                            ->end()
                            ->arrayNode('ssh')->isRequired()
                                ->children()
                                    ->enumNode('proxy')
                                        ->values(array('cli', 'pecl'))
                                    ->end()
                                    ->scalarNode('user')->isRequired()
                                    ->end()
                                    ->scalarNode('password')
                                    ->end()
                                    ->scalarNode('public_key_file')
                                    ->end()
                                    ->scalarNode('private_key_file')->isRequired()
                                    ->end()
                                    ->scalarNode('private_key_file_pwd')
                                    ->end()
                                ->end()
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
                            ->enumNode('environment')
                                ->isRequired()
                                ->values(array('prod', 'dev', 'test', 'stage'))
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
                            ->arrayNode('helper')
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
