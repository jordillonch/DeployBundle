<?php

namespace JordiLlonch\Bundle\DeployBundle\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class Engine
{
    protected $zoneManager;
    protected $dryMode = false;

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
    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    /**
     * @param boolean $dryMode
     */
    public function setDryMode($dryMode)
    {
        $this->dryMode = $dryMode;
    }

    /**
     * Set deployer in mode to deploy only for selected zones
     *
     * @param array $selectedZones
     */
    public function setSelectedZones(array $selectedZones)
    {
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
    public function runDownloadCode()
    {
        $this->logger->debug('[Download]');

        // create new version
        $new_version = date("Ymd_His");
        $funcRollback = function($zone, $dryMode) {
            $zone->runDownloadCodeRollback();
            if (!$dryMode) $zone->setNewVersionRollback();
        };
        $this->call('runDownloadCode', array($new_version), $funcRollback);
    }

    /**
     * Put code in production
     */
    public function runCode2Production()
    {
        $this->logger->debug('[Code to production]');

        $funcRollback = function($zone, $dryMode) {
          $zone->runCode2ProductionRollback();
        };
        $this->call('runCode2Production', array(), $funcRollback);
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
    protected function call($name, $arguments, $lambdaRollback, $silent=false)
    {
        try
        {
            $response = array();
            foreach($this->zoneManager->getZonesNames() as $zone)
            {
                if(!$silent) $this->output->writeln('<info>[' . $zone . ']</info>');
                $response[$zone] = call_user_func_array(array($this->zoneManager->getZone($zone), $name), $arguments);
            }

            return $response;
        }
        catch (\Exception $e)
        {
            foreach($this->zoneManager->getZonesNames() as $zone)
            {
                if(!$silent) $this->output->writeln('<error>ROLLBACK [' . $zone . ']</error>');
                try
                {
                    $zoneObj = $this->zoneManager->getZone($zone);
                    $lambdaRollback($zoneObj, $this->dryMode);
                }
                catch (\Exception $e) {}

            }

            // Log error
            $this->logger->crit($e->getMessage());
        }
    }

    public function getZonesNames()
    {
        return $this->zoneManager->getZonesNames();
    }
}
