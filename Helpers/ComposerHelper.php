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

class ComposerHelper extends Helper {
    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'composer';
    }

    /**
     * Install composer.phar in the new repository dir
     */
    public function install()
    {
        $this->getDeployer()->exec('curl -sS https://getcomposer.org/installer | php -- --install-dir=' . $this->getDeployer()->getLocalNewRepositoryDir());
    }

    /**
     * Executes composer install in the new repository dir
     * If environment is dev or test --dev parameter is added to composer install
     * If environment is prod --no-dev parameter is added to composer install
     */
    public function executeInstall()
    {
        $composerNoDev = '';
        if ($this->getDeployer()->getEnvironment() == 'dev')  $composerNoDev = ' --dev';
        if ($this->getDeployer()->getEnvironment() == 'test') $composerNoDev = ' --dev';
        if ($this->getDeployer()->getEnvironment() == 'prod') $composerNoDev = ' --no-dev';
        $this->getDeployer()->exec('php ' . $this->getDeployer()->getLocalNewRepositoryDir() . '/composer.phar --working-dir=' . $this->getDeployer()->getLocalNewRepositoryDir() . ' install --optimize-autoloader' . $composerNoDev);
    }
}