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

trait Composer {
    /**
     * Install composer.phar in the new repository dir
     */
    protected function helperComposerInstall()
    {
        $this->exec('curl -sS https://getcomposer.org/installer | php -- --install-dir=' . $this->getLocalNewRepositoryDir());
    }

    /**
     * Executes composer install in the new repository dir
     * If environment is dev or test --dev parameter is added to composer install
     * If environment is prod --no-dev parameter is added to composer install
     */
    protected function helperComposerExecuteInstall()
    {
        $composerNoDev = '';
        if ($this->environment == 'dev')  $composerNoDev = ' --dev';
        if ($this->environment == 'test') $composerNoDev = ' --dev';
        if ($this->environment == 'prod') $composerNoDev = ' --no-dev';
        $this->exec('php ' . $this->getLocalNewRepositoryDir() . '/composer.phar --working-dir=' . $this->getLocalNewRepositoryDir() . ' install --optimize-autoloader' . $composerNoDev);
    }
}