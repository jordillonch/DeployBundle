<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class Engine
{
    protected $zoneManager;
    protected $dryMode = false;
    protected $silent = null;
    protected $configGeneral = array();
    protected $configZones = array();
    protected $helper = array();

    /**
     * @var LockInterface
     */
    protected $lock;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ZoneManager $zoneManager
     * @param $dryMode
     */
    public function __construct(ZoneManager $zoneManager, LockInterface $lock)
    {
        $this->zoneManager = $zoneManager;
        $this->lock = $lock;
    }

    /**
     * @param array $config
     */
    public function setConfigGeneralConfig(array $config)
    {
        $this->configGeneral = $config;
        if(isset($config['helper'])) $this->helper = $config['helper'];
    }

    /**
     * @param array $config
     */
    public function setConfigZonesConfig(array $config)
    {
        $this->configZones = $config;
    }

    /**
     * @param boolean $dryMode
     */
    public function setDryMode($dryMode)
    {
        $this->dryMode = $dryMode;
    }

    /**
     * @param boolean $silent
     */
    public function setSilent($silent)
    {
        $this->silent = $silent;
    }

    /**
     * Set deployer in mode to deploy only for selected zones
     *
     * @param array $selectedZones
     */
    public function setSelectedZones(array $selectedZones)
    {
        if(empty($selectedZones) || empty($selectedZones[0])) throw new \Exception('Zones is required');

        // Check if all $selectedZones exist
        if(count(array_diff($selectedZones, $this->zoneManager->getZonesNames()))) throw new \Exception('Zone does not exist.');

        foreach($this->zoneManager->getZonesNames() as $zone)
        {
            if(!in_array($zone, $selectedZones)) $this->zoneManager->removeZone($zone);
        }
    }

    /**
     * Set output
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        // Engine
        $this->output = $output;

        // Zones
        $lambdaRollback = function($a, $b) {};
        $this->call('setOutput', array($output), $lambdaRollback, true);
    }

    /**
     * Set logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        // Engine
        $this->logger = $logger;

        // Zones
        $lambdaRollback = function($a, $b) {};
        $this->call('setLogger', array($logger), $lambdaRollback, true);
    }

   /**
     * Get status
     */
    public function getStatus()
    {
        // Zones
        $lambdaRollback = function($a, $b) {};
        return $this->call('getStatus', array(), $lambdaRollback, true);
    }

//    protected function getDataDir()
//    {
//        return $this->repository_dir . "/data";
//    }

    /**
     * Download code that it will be put in production
     */
    public function runDownloadCode($branch = null)
    {
        $this->logger->debug('[Download]');

        // create new version
        $newVersion = date("Ymd_His");
        $funcRollback = function(BaseDeployer $zone, $dryMode) {
            $zone->runDownloadCodeRollback();
            if (!$dryMode) $zone->setNewVersionRollback();
        };
        if ($branch) $this->call('updateBranch', array($branch), function(){}, true);
        return $this->call('runDownloadCode', array($newVersion, $branch), $funcRollback);
    }

    /**
     * Put code in production
     */
    public function runCode2Production()
    {
        $this->logger->debug('[Code to production]');

        $funcRollback = function(BaseDeployer $zone, $dryMode) {
          if(!$dryMode) $zone->runCode2ProductionRollback();
        };
        return $this->call('runCode2Production', array(), $funcRollback);
    }

    /**
     * Execute any method on zones
     *
     * @param $name Method name
     * @param $arguments Arguments
     * @return array Result on every zone executed
     */
    public function __call($name, $arguments)
    {
        $this->logger->debug('[' . $name . ']');

        $lambdaRollback = function($a, $b) {};

        return $this->call($name, $arguments, $lambdaRollback);
    }

    /**
     * Base execute method for every zone
     *
     * @param $name
     * @param $arguments
     * @param $lambdaRollback
     * @param bool $silent
     * @return array
     */
    protected function call($name, $arguments, $lambdaRollback, $silent=null)
    {
        $response = $this->callForEveryZone($name, $arguments, $lambdaRollback, $silent);

        return $response;
    }

    public function getZonesNames()
    {
        return $this->zoneManager->getZonesNames();
    }

    /**
     * @return array
     */
    public function getHelpersConfig()
    {
        return $this->helper;
    }

    /**
     * @param $name
     * @param $arguments
     * @param $lambdaRollback
     * @param $silent
     * @return array
     */
    protected function callForEveryZone($name, $arguments, $lambdaRollback, $silent=null)
    {
        // Silent
        if(!is_null($this->silent)) $silent = $this->silent;
        if(is_null($silent)) $silent = false;

        $response = array();
        try {
            foreach ($this->zoneManager->getZonesNames() as $zone) {
                if (!$silent) $this->output->writeln('<info>[' . $zone . ']</info>');
                $response[$zone] = call_user_func_array(array($this->zoneManager->getZone($zone), $name), $arguments);
            }
        } catch (\Exception $e) {
            // Log error
            $this->logger->critical($e->getMessage());

            foreach ($this->zoneManager->getZonesNames() as $zone) {
                if (!$silent) $this->output->writeln('<error>ROLLBACK [' . $zone . ']</error>');
                try {
                    $zoneObj = $this->zoneManager->getZone($zone);
                    $lambdaRollback($zoneObj, $this->dryMode);
                } catch (\Exception $e) {
                    // Log error
                    $this->logger->critical($e->getMessage());
                }

                $response[$zone] = false;
            }

            // Log error
            $this->logger->critical($e->getMessage());
        }

        return $response;
    }

    public function adquireZonesLockOrThrowException()
    {
        if (!$this->adquireZonesLock()) throw new \Exception('There are locked zones. Try again in a while.');
    }

    protected function adquireZonesLock()
    {
        $allZonesLockAdquired = true;
        foreach ($this->zoneManager->getZonesNames() as $zone) {
            if(!$this->lock->acquire($zone)) {
                $allZonesLockAdquired = false;
                break;
            }
        }

        if(!$allZonesLockAdquired) {
            $this->lock->releaseAll();
        }

        return $allZonesLockAdquired;
    }

    public function releaseZonesLock()
    {
        $this->lock->releaseAll();
    }
}
