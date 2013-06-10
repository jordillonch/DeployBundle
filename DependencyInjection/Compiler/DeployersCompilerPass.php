<?php
/**
 * @author Jordi Llonch <llonch.jordi@gmail.com>
 * @date 24/04/13 13:41
 */

namespace JordiLlonch\Bundle\DeployBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DeployersCompilerPass implements CompilerPassInterface
{

    /**
     * Define Channel Configuration
     *
     * @param  ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $generalConfig = $container->getParameter('jordi_llonch_deploy.config');
        $zonesConfig = $container->getParameter('jordi_llonch_deploy.zones');
        if (empty($zonesConfig)) {
            throw new \Exception("Configure jordi_llonch_deploy zones on config.yml", 1);
        }
        if (empty($generalConfig)) {
            throw new \Exception("Configure jordi_llonch_deploy config on config.yml", 1);
        }

        $this->defineChannelsConfiguration($container, $generalConfig, $zonesConfig);
    }

    /**
     * Define Plugin Config Parameters
     *
     * @param ContainerBuilder $container
     * @param array $generalConfig
     */
    protected function defineChannelsConfiguration(ContainerBuilder $container, array $generalConfig, array $zonesConfig)
    {
        $this->processTaggedDeployers($container);

        $zoneManagerDefinition = new Definition('JordiLlonch\Bundle\DeployBundle\Library\ZoneManager');
        foreach ($zonesConfig as $name => $zoneConfig) {
            // Add zone
            $pluginId = $this->getDeployer($zoneConfig['deployer']);
            $zoneDefinition = clone $container->getDefinition($pluginId);
            $zoneDefinition->addMethodCall('setZoneName', array($name));
            if(!isset($generalConfig['local_repository_dir'])) $generalConfig['local_repository_dir'] = $container->getParameter('kernel.cache_dir') . '/jordi_llonch_deploy';
            $zoneDefinition->addMethodCall('setConfig', array($generalConfig, $zonesConfig));

            // Add zone to the zone manager
            $zoneManagerDefinition->addMethodCall('addZone', array($zoneDefinition));
        }

        // Deployer engine
        $engine = new Definition('JordiLlonch\Bundle\DeployBundle\Library\Engine', array($zoneManagerDefinition, $generalConfig['dry_mode']));

        // service that developers will use
        $container->setDefinition('jordi_llonch_deploy.engine', $engine);


        // TODO why is not using services.xml?
        $configure = new Definition('JordiLlonch\Bundle\DeployBundle\Library\Configure', array());
        $container->setDefinition('jordi_llonch_deploy.configure', $configure);
    }

    /**
     * Process Deployer Ids by type for better access
     *
     * @param Container $container
     */
    protected function processTaggedDeployers(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('jordi_llonch_deploy') as $id => $attributes) {
            $this->deployers[$attributes[0]['deployer']] = $id;
        }
    }

    /**
     * Returns Deployer Id
     *
     * @param string $deployerName
     *
     * @return Deployer object
     */
    protected function getDeployer($deployerName)
    {
        if (!isset($this->deployers[$deployerName])) {
            throw new \Exception (sprintf('Deployer %s not found', $deployerName));
        }

        return $this->deployers[$deployerName];
    }
}