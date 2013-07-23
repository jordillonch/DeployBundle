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

Class ZoneManager
{
    /**
     * Array Configured Zones
     * @var array
     */
    protected $zones = array();

    /**
     * Add Zone to ZoneManager Pool
     * @param $zone
     */
    public function addZone(BaseDeployer $zone)
    {
        $this->zones[$zone->getZoneName()] = $zone;
    }

    /**
     * Get Zone from Pool
     * @param string $name Zone Name
     * @return
     */
    public function getZone($name)
    {
        if (!isset($this->zones[$name])) {
            throw new \Exception(sprintf('Zone %s not found', $name));
        }

        return $this->zones[$name];
    }

    /**
     *
     * @param $zone
     * @return [type]          [description]
     */
    protected function getZoneName(BaseDeployer $zone)
    {
        return $zone->getName();
    }

    public function getZonesNames()
    {
        return array_keys($this->zones);
    }

    public function removeZone($zone)
    {
        unset($this->zones[$zone]);
    }
}
