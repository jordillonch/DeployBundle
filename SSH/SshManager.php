<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace JordiLlonch\Bundle\DeployBundle\SSH;


use Psr\Log\LoggerInterface;

class SshManager {
    protected $proxy;
    protected $parameters;
    protected $cache = array();
    protected $logger = null;

    public function __construct(array $parameters) {
        $proxy = null;
        if(isset($parameters['proxy'])) {
            switch($parameters['proxy']) {
                case 'cli':
                    $proxy = new CLISshProxy();
                    break;
                case 'pecl':
                    $proxy = new PeclSsh2Proxy();
                    break;
                case 'local':
                    $proxy = new LocalhostProxy();
                    break;
            }
        } else {
            if(extension_loaded ('php-ssh2'))
            {
                $proxy = new PeclSsh2Proxy();
            } else {
                $proxy = new CLISshProxy();
            }

        }

        $this->proxy = $proxy;
        $this->parameters = $parameters;
    }

    function __destruct()
    {
        foreach ($this->cache as $server => $sshClient) {
            if($this->logger) $this->logger->debug('[' . $server . '] Disconnecting SshClient...');
            $sshClient->disconnect();
        }

    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function exec(array $servers, $command) {
        foreach ($servers as $server) {
            $ssh = $this->getSshClient($server);
            if($this->logger) $this->logger->debug('[' . $server . '] remote exec: ' . $command);
            $r[$server]['exit_code'] = $ssh->exec($command);
            $r[$server]['output'] = $ssh->getLastOutput();
            $r[$server]['error'] = $ssh->getLastError();

            // Bypass error for 'tcgetattr: Invalid argument'
            if(strpos($r[$server]['error'], 'tcgetattr: Invalid argument') !== false) {
                $this->logger->debug('bypassed error: ' . $r[$server]['error']);
                $r[$server]['error'] = '';
                $r[$server]['exit_code'] = 0;
            }
        }

        return $r;
    }

    protected function getSshClient($server)
    {
        if(isset($this->cache[$server])) {
            if($this->logger) $this->logger->debug('SshClient from cache (' . $server . ')');

            return $this->cache[$server];
        }

        list($host, $port) = $this->extractHostPort($server);
        $proxy = clone $this->proxy;
        if($host == 'localhost') $proxy = new LocalhostProxy();
        $ssh = new SshClient($proxy);
        if($this->logger) $ssh->setLogger($this->logger);
        $parameters = $this->parameters;
        $parameters['ssh_port'] = $port;
        $ssh->setParameters($parameters);
        $ssh->setHost($host);
        if($this->logger) $this->logger->debug('SshClient connecting to ' . $server . '...');
        $ssh->connect();
        if($this->logger) $this->logger->debug('SshClient connected');

        $this->cache[$server] = $ssh;

        return $ssh;
    }

    /**
     * @param $server
     * @return array
     */
    protected function extractHostPort($server)
    {
        $expServer = explode(':', $server);
        $host = $expServer[0];
        $port = 22;
        if (isset($expServer[1])) $port = $expServer[1];

        return array($host, $port);
    }
}