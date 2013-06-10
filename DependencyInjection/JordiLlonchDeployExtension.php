<?php

namespace JordiLlonch\Bundle\DeployBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

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

        //@TODO: Waiting Configuration validation
        $config = $this->processConfiguration($configuration, $configs);
//        $config = $configs[0];
//ladybug_dump($config);
//        $container->setParameter('jordi_llonch_deploy.config', $config);


        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');



//        $configuration = new Configuration();
//        $config = $this->processConfiguration($configuration, $configs);
//
////        $laigu_deployer = array();
////        foreach ($config as $parameter => $value) {
////            $container->setParameter('laigu-deployer.'.$parameter, $value);
////            $laigu_deployer[$parameter] = $value;
////        }
////
////        if(!isset($laigu_deployer['local_repository_dir']))
////        {
////            $laigu_deployer['local_repository_dir'] = $container->getParameter("kernel.cache_dir").'/laiguDeployer';
////            $container->setParameter('laigu-deployer.local_repository_dir', $laigu_deployer['local_repository_dir']);
////        }
////
////        $container->setParameter('laigu-deployer', $laigu_deployer);
//
//
//        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
//        $loader->load('services.yml');
    }
}
