<?php
/**
 * @author Jordi Llonch <llonch.jordi@gmail.com>
 * @date 23/05/13 15:16
 */

namespace JordiLlonch\Bundle\DeployBundle\Service;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configure
 * @package JordiLlonch\Bundle\DeployBundle\Service
 */
class Configure
{
    protected $path;
    protected $parameters;
    protected $zones;

    const OUTPUT_YML = 0;
    const OUTPUT_JSON = 1;

    /**
     * @param $path
     */
    public function readParametersFile($path)
    {
        $this->path = $path;
        $yml = file_get_contents($this->path);
        $this->parameters = Yaml::parse($yml);
        $this->zones = $this->parameters['parameters']['jordi_llonch_deploy.zones'];
    }

    /**
     *
     */
    public function writeParametersFile()
    {
        $this->parameters['parameters']['jordi_llonch_deploy.zones'] = $this->zones;
        $yml = Yaml::dump($this->parameters, 5);
        file_put_contents($this->path, $yml);
    }

    /**
     * @param $zone
     * @param $url
     */
    public function set($zone, $url)
    {
        $this->checkZone($zone);
        $url = $this->sanitizeUrl($url);
        $this->zones[$zone]['urls'] = $url;
    }

    /**
     * @param $zone
     * @param $url
     */
    public function add($zone, $url)
    {
        $this->checkZone($zone);
        $url = $this->sanitizeUrl($url);
        $url = array_merge($this->zones[$zone]['urls'], $url);
        $url = array_unique($url);
        $this->zones[$zone]['urls'] = $url;
    }

    /**
     * @param $zone
     * @param $url
     */
    public function rm($zone, $url)
    {
        $this->checkZone($zone);
        $url = $this->sanitizeUrl($url);
        $currentUrls = $this->zones[$zone]['urls'];
        $newUrls = array_values(array_filter($currentUrls, function($item) use($url) {
           return !in_array($item, $url);
        }));
        $this->zones[$zone]['urls'] = $newUrls;
    }

    /**
     * @param $zone
     * @param int $format
     * @return string
     */
    public function listUrls($zone, $format = self::OUTPUT_YML)
    {
        $this->checkZone($zone);
        switch($format)
        {
            case self::OUTPUT_JSON:
                $output = json_encode($this->zones[$zone]['urls']);
                break;
            case self::OUTPUT_YML:
                $output = Yaml::dump($this->zones[$zone]['urls']);
                break;
        }

        return $output;
    }

    /**
     * @param $zone
     * @return bool
     */
    protected function existsZone($zone)
    {
        return isset($this->zones[$zone]);
    }

    /**
     * @param $url
     * @return array
     */
    protected function sanitizeUrl($url)
    {
        $url = explode(',', $url);
        $url = array_map(function($item) { return trim($item); }, $url);

        return $url;
    }

    /**
     * @param $zone
     * @throws \Exception
     */
    protected function checkZone($zone)
    {
        if (!$this->existsZone($zone)) throw new \Exception('Zone ' . $zone . ' does not exists.');
    }
}
