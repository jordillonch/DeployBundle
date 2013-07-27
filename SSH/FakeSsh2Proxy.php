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

class FakeSsh2Proxy extends BaseProxy
{
    public function __construct($test)
    {
        $this->test = $test;
    }

    public function connect($host, $port)
    {
        if ('fail_connection' === $host) {
            return false;
        }

        $this->test->assertTrue(true);

        return true;
    }

    public function authByPassword($user, $pwd)
    {
        $this->test->assertTrue(true);

        return true;
    }

    public function authByPublicKey($user, $public_key_file, $private_key_file, $pwd)
    {
        $this->test->assertTrue(true);

        return true;
    }

    public function authByAgent($user)
    {
        $this->test->assertTrue(true);

        return true;
    }

    public function exec($cmd)
    {
        $this->test->assertTrue(true);
        $this->lastOutput = 'test out '.$cmd;
        $this->lastError = 'test err '.$cmd;

        return $cmd;
    }

    public function isConnected()
    {
        $this->test->assertTrue(true, 'isConnected');

        return true;
    }
}