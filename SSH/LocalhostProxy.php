<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\SSH;

class LocalhostProxy extends BaseProxy
{
    public function connect($host, $port)
    {
        return true;
    }

    public function authByPassword($user, $pwd)
    {
        return true;
    }

    public function authByPublicKey($user, $public_key_file, $private_key_file, $pwd)
    {
        return true;
    }

    public function authByAgent($user)
    {
        return true;
    }

    /**
     * @param string $cmd the command to be execute
     *
     * @return true in case of success, false otherwise
     */
    public function exec($cmd)
    {
        if($this->logger) $this->logger->debug('localhost exec: ' . $cmd);

        $outputLastLine = exec($cmd, $output, $returnVar);
        if ($returnVar != 0) throw new \Exception('ERROR executing: ' . $cmd . "\n" . implode("\n", $output));

        if(!empty($output)) foreach($output as $item) $this->logger->debug('exec output: ' . $item);

        if($returnVar == 0) $this->lastOutput = $outputLastLine;
        else $this->lastError = $outputLastLine;

        return $returnVar;
    }

}