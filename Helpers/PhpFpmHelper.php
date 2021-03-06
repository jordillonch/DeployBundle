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

class PhpFpmHelper extends Helper {
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'phpfpm';
    }

    /**
     * Refresh php-fpm gracefully but you must configure your webserver (e.g. Nginx) to retry the request
     * @throws \Exception
     */
    public function refresh()
    {
        try
        {
            $this->getDeployer()->execRemoteServers($this->refreshCommand());
        }
        catch(\Exception $e)
        {
            $newExcepcion = new \Exception($e->getMessage() . 'Probably php-fpm is not working!', $e->getCode());

            throw $newExcepcion;
        }
    }

    /**
     * Command used to reload php-fpm
     * @return string
     */
    public function refreshCommand()
    {
        // WARNING: php-fpm has to be running, otherwise pkill exits with 1 code and it is detected as an error
        return 'sudo pkill -USR2 -o php-fpm';
    }
}