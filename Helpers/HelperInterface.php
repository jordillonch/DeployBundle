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

interface HelperInterface {
    /**
     * Define helper name.
     * It is used to get helper from HelperSet.
     * Also it is used to set configuration by HelperSet.
     *
     * @return string
     */
    public function getName();

    /**
     * Sets the Deployer to helper.
     *
     * @param DeployerInterface $deployer
     */
    public function setDeployer(DeployerInterface $deployer);

    /**
     * Gets Deployer.
     *
     * @return DeployerInterface
     */
    public function getDeployer();

    /**
     * Sets configuration for helper.
     *
     * @param array $config
     */
    public function setConfig(array $config);

    /**
     * Gets helper configuration.
     *
     * @return array
     */
    public function getConfig();
}