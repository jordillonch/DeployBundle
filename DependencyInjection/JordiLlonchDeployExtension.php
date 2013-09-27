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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class JordiLlonchDeployExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $configs = $this->mergeZonesServers($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('jordi_llonch_deploy.configured.config', $config['config']);
        $container->setParameter('jordi_llonch_deploy.configured.zones', $config['zones']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    protected function mergeZonesServers(array $configs, ContainerBuilder $container)
    {
        foreach ($configs as $key => $config) {
            if(isset($config['config']['servers_parameter_file'])) {
                $file = $container->getParameter('kernel.root_dir') . '/../' . $config['config']['servers_parameter_file'];
                $configServers = Yaml::parse($file);
                $config['zones'] = \array_replace_recursive($config['zones'], $configServers);
                $configs[$key] = $config;
            }
        }

        return $configs;
    }
}
