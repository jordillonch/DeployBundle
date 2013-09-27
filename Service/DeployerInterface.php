<?php
/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Service;

use JordiLlonch\Bundle\DeployBundle\VCS\VcsInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

interface DeployerInterface {
    // Zones
    public function setZoneName($name);
    public function getZoneName();

    // Config
    public function setConfig(array $generalConfig, array $zonesConfig);
    public function getOtherZoneConfig($zoneName, $parameterName);

    public function setOutput(OutputInterface $output);
    public function setLogger(LoggerInterface $logger);
    public function getLogger();

    public function getCustom();
    public function setSshManager($sshManager);
    public function getSshManager();
    public function setVcs(VcsInterface $vcs);
    public function getVcs();

    public function initialize();

    public function code2ProductionBefore();
    public function code2ProductionBeforeRollback();
    public function code2ProductionAfter();
    public function code2ProductionAfterRollback();

    public function downloadCode();
    public function downloadCodeRollback();
    public function exec($command, &$output = null);
    public function execRemoteServers($command, $urls = null);
    public function rsync($originPath, $server, $serverPath, $rsyncParams = '');
    public function rsync2Servers($originPath, $targetPath, $rsyncParams = '');
    public function syncronize($rsyncParams = '');

    // Local paths
    public function getLocalRepositoryDir();
    public function getLocalCodeDir();
    public function getLocalDataDir();
    public function getLocalDataCurrentVersionFile();
    public function getLocalDataNewVersionFile();
    public function getLocalNewRepositoryDir();
    public function getLocalCurrentCodeDir();

    // Remote paths
    public function getRemoteRepositoryDir();
    public function getRemoteBinDir();
    public function getRemoteSharedDir();
    public function getRemoteProductionCodeDir();
    public function getRemoteCodeDir();
    public function getRemoteCurrentRepositoryDir();
    public function getRemoteNewRepositoryDir();

    public function getEnvironment();
    public function getHelpersConfig();
    public function getHashFromCurrentCodeToNewRepository();
    public function getSudo();
}