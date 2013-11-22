<?php


/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DeployersCompilerPass implements CompilerPassInterface
{

    /**
     * Define Deployers Configuration
     *
     * @param  ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $generalConfig = $container->getParameter('jordi_llonch_deploy.configured.config');
        $zonesConfig = $container->getParameter('jordi_llonch_deploy.configured.zones');
        if (empty($zonesConfig)) {
            throw new \Exception("Configure jordi_llonch_deploy zones on config.yml", 1);
        }
        if (empty($generalConfig)) {
            throw new \Exception("Configure jordi_llonch_deploy config on config.yml", 1);
        }

        $this->defineDeployersConfiguration($container, $generalConfig, $zonesConfig);
    }

    /**
     * Define Plugin Config Parameters
     *
     * @param ContainerBuilder $container
     * @param array $generalConfig
     * @param array $zonesConfig
     */
    protected function defineDeployersConfiguration(ContainerBuilder $container, array $generalConfig, array $zonesConfig)
    {
        $this->processTaggedDeployers($container);

        $zoneManagerDefinition = new Definition('JordiLlonch\Bundle\DeployBundle\Service\ZoneManager');
        foreach ($zonesConfig as $name => $zoneConfig) {
            // Add zone
            $pluginId = $this->getDeployer($zoneConfig['deployer']);
            $zoneDefinition = clone $container->getDefinition($pluginId);
            $zoneDefinition->addMethodCall('setZoneName', array($name));
            $zoneDefinition->addMethodCall('setConfig', array($generalConfig, $zonesConfig));

            // Add zone to the zone manager
            $zoneManagerDefinition->addMethodCall('addZone', array($zoneDefinition));
        }

        // Deployer engine
        $engineClass = $container->getParameter('jordillonch_deployer.engine.class');
        $engine = new Definition($engineClass, array($zoneManagerDefinition, $container->findDefinition('jordillonch_deployer.locker')));
        $engine->addMethodCall('setConfigGeneralConfig', array($generalConfig));
        $engine->addMethodCall('setConfigZonesConfig', array($zonesConfig));

        // service that developers will use
        $container->setDefinition('jordillonch_deployer.engine', $engine);
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