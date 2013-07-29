<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Helpers;

use JordiLlonch\Bundle\DeployBundle\Service\DeployerInterface;

/**
 * HelperSet represents a set of helpers to be used with a command.
 */
class HelperSet
{
    /**
     * Array of helper.
     *
     * @var array
     */
    protected $helpers = array();

    /**
     * Constructor.
     *
     * @param Helper[] $helpers An array of helper.
     */
    public function __construct(array $helpers = array())
    {
        $this->helpers = array();
        foreach ($helpers as $alias => $helper) {
            $this->set($helper, is_int($alias) ? null : $alias);
        }
    }

    /**
     * Sets a helper.
     *
     * @param HelperInterface $helper The helper instance
     * @param string          $alias  An alias
     */
    public function set(HelperInterface $helper, $alias = null)
    {
        $this->helpers[$helper->getName()] = $helper;
        if (null !== $alias) {
            $this->helpers[$alias] = $helper;
        }

        $helper->setHelperSet($this);
    }

    /**
     * Returns true if the helper if defined.
     *
     * @param string $name The helper name
     *
     * @return Boolean true if the helper is defined, false otherwise
     */
    public function has($name)
    {
        return isset($this->helpers[$name]);
    }

    /**
     * Gets a helper value.
     *
     * @param string $name The helper name
     *
     * @return HelperInterface The helper instance
     *
     * @throws \Exception if the helper is not defined
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \Exception(sprintf('The helper "%s" is not defined.', $name));
        }

        return $this->helpers[$name];
    }

    /**
     * Sets the Deployer for each helper.
     * Also sets config from deployer. It must be defined under a key with same name as helper.
     *
     * @param DeployerInterface $deployer
     */
    public function setDeployer(DeployerInterface $deployer)
    {
        foreach ($this->helpers as $helper) {
            // Set deployer
            $helper->setDeployer($deployer);

            // Set config from deployer
            // It must be defined under a key with same name as helper
            $helpersConfig = $deployer->getHelpersConfig();
            if(isset($helpersConfig[$helper->getName()])) $helper->setConfig($helpersConfig[$helper->getName()]);
        }
    }
}
