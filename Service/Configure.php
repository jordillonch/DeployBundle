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

use Symfony\Component\Yaml\Yaml;

/**
 * Class Configure
 * @package JordiLlonch\Bundle\DeployBundle\Service
 */
class Configure
{
    protected $path;
    protected $parameters;
    protected $parametersInit;

    const OUTPUT_YML = 0;
    const OUTPUT_JSON = 1;
    const OUTPUT_ARRAY = 2;

    /**
     * @param $path
     */
    public function readParametersFile($path)
    {
        $this->path = $path;
        $yml = file_get_contents($this->path);
        $this->parameters = Yaml::parse($yml);
        $this->parametersInit = $this->parameters;
    }

    /**
     *
     */
    public function writeParametersFile()
    {
        // Only write if are modifications
        if($this->parameters == $this->parametersInit) return;

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
        $this->parameters[$zone]['urls'] = $url;
    }

    /**
     * @param $zone
     * @param $url
     */
    public function add($zone, $url)
    {
        $this->checkZone($zone);
        $url = $this->sanitizeUrl($url);
        $url = array_merge($this->parameters[$zone]['urls'], $url);
        $url = array_unique($url);
        $this->parameters[$zone]['urls'] = $url;
    }

    /**
     * @param $zone
     * @param $url
     */
    public function rm($zone, $url)
    {
        $this->checkZone($zone);
        $url = $this->sanitizeUrl($url);
        $currentUrls = $this->parameters[$zone]['urls'];
        $newUrls = array_values(array_filter($currentUrls, function($item) use($url) {
           return !in_array($item, $url);
        }));
        $this->parameters[$zone]['urls'] = $newUrls;
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
                $output = json_encode($this->parameters[$zone]['urls']);
                break;
            case self::OUTPUT_YML:
                $output = Yaml::dump($this->parameters[$zone]['urls']);
                break;
            case self::OUTPUT_ARRAY:
                $output = $this->parameters[$zone]['urls'];
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
        return isset($this->parameters[$zone]);
    }

    /**
     * @param $url
     * @return array
     */
    public function sanitizeUrl($url)
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
