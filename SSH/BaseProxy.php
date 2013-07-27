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

use Psr\Log\LoggerInterface;

abstract class BaseProxy implements ProxyInterface
{
    protected $connection = null;
    protected $lastError;
    protected $lastOutput;

    protected $logger = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function disconnect()
    {
        $this->connection = null;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getLastOutput()
    {
        return $this->lastOutput;
    }
}