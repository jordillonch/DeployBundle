<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This class is based in Idephix - Automation and Deploy tool
 * https://github.com/ideatosrl/Idephix
 *
 */

namespace JordiLlonch\Bundle\DeployBundle\SSH;

interface ProxyInterface
{
    public function connect($host, $port);

    public function authByPassword($user, $pwd);
    public function authByPublicKey($user, $public_key_file, $private_key_file, $pwd);
    public function authByAgent($user);

    /**
     * @param string $cmd the command to be execute
     *
     * @return true in case of success, false otherwise
     */
    public function exec($cmd);

    public function getLastError();
    public function getLastOutput();
}