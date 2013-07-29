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

abstract class Helper implements HelperInterface {
    /**
     * @var DeployerInterface
     */
    protected $deployer;

    /**
     * @var array
     */
    protected $config = array();

    /**
     * @var HelperSet
     */
    protected $helperSet = null;

    /**
     * Sets the helper set associated with this helper.
     *
     * @param HelperSet $helperSet A HelperSet instance
     */
    public function setHelperSet(HelperSet $helperSet = null)
    {
        $this->helperSet = $helperSet;
    }

    /**
     * Gets the helper set associated with this helper.
     *
     * @return HelperSet A HelperSet instance
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeployer(DeployerInterface $deployer) {
        $this->deployer = $deployer;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeployer() {
        return $this->deployer;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }
}