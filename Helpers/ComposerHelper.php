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
    public function install($installDirectory = null)
    {
        if (is_null($installDirectory)) $installDirectory = $this->getDeployer()->getLocalNewRepositoryDir();
        $this->getDeployer()->exec('curl -sS https://getcomposer.org/installer | php -- --install-dir=' . $installDirectory);
    }

    /**
     * Executes composer install in the new repository dir
     * If environment is dev or test --dev parameter is added to composer install
     * If environment is prod --no-dev parameter is added to composer install
     */
    public function executeInstall($workingDirectory = null, $env = null)
    {
        if (is_null($workingDirectory)) $workingDirectory = $this->getDeployer()->getLocalNewRepositoryDir();
        $composerNoDev = '';
        if (is_null($env)) $env = $this->getDeployer()->getEnvironment();
        if ($env == 'dev')  $composerNoDev = ' --dev';
        if ($env == 'test') $composerNoDev = ' --dev';
        if ($env == 'prod') $composerNoDev = ' --no-dev';
        $this->getDeployer()->exec('php ' . $workingDirectory . '/composer.phar --working-dir=' . $workingDirectory . ' install --optimize-autoloader' . $composerNoDev);
    }
}