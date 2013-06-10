<?php

namespace JordiLlonch\Bundle\DeployBundle\Library;

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
    public function addZone($zone, $id = null)
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
     * @param  [type] $id      deployer $id
     * @return [type]          [description]
     */
    protected function getZoneName($zone, $id = null)
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
