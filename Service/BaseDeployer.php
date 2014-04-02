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

use JordiLlonch\Bundle\DeployBundle\Helpers\ComposerHelper;
use JordiLlonch\Bundle\DeployBundle\Helpers\GitHubHelper;
use JordiLlonch\Bundle\DeployBundle\Helpers\HelperSet;
use JordiLlonch\Bundle\DeployBundle\Helpers\HipChatHelper;
use JordiLlonch\Bundle\DeployBundle\Helpers\PhpFpmHelper;
use JordiLlonch\Bundle\DeployBundle\Helpers\SharedDirsHelper;
use JordiLlonch\Bundle\DeployBundle\Helpers\Symfony2;
use JordiLlonch\Bundle\DeployBundle\Helpers\Symfony2Helper;
use JordiLlonch\Bundle\DeployBundle\SSH\SshManager;
use JordiLlonch\Bundle\DeployBundle\VCS\VcsFactory;
use JordiLlonch\Bundle\DeployBundle\VCS\VcsInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

abstract class BaseDeployer implements DeployerInterface
{
    protected $project;
    protected $environment;
    protected $id;
    protected $urls;
    protected $localRepositoryDir;
    protected $remoteRepositoryDir;
    protected $remoteRepositoryDirRollback;
    protected $remoteProductionDir;
    protected $location;
    protected $checkoutUrl;
    protected $checkoutBranch;
    protected $checkoutProxy = false;
    protected $cleanMaxDeploys = 7;
    protected $dryMode = false;
    protected $force = false;
    protected $newVersion;
    protected $currentVersion;
    protected $output;
    protected $sudo = false;
    protected $custom;
    protected $helpersConfig;
    protected $newVersionRollback;
    protected $currentVersionRollback;
    protected $vcsType;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    protected $rollingBack = false;
    protected $rollingBackFromVersion;
    protected $rollingBackToVersion;
    protected $zonesConfig;
    protected $sshConfig;

    /**
     * @var SshManager
     */
    protected $sshManager;

    /**
     * @var \JordiLlonch\Bundle\DeployBundle\Helpers\HelperSet
     */
    protected $helperSet;

    /**
     * @var VcsInterface
     */
    protected $vcs;

    public function __construct()
    {
        $this->helperSet = $this->getDefaultHelperSet();
    }

    /**
     * Gets the default helper set with the helpers that should always be available.
     *
     * @return HelperSet A HelperSet instance
     */
    protected function getDefaultHelperSet()
    {
        return new HelperSet($this->getDefaultHelpers());
    }

    /**
     * @return array
     */
    protected function getDefaultHelpers()
    {
        return array(
            new SharedDirsHelper(),
            new ComposerHelper(),
            new GitHubHelper(),
            new HipChatHelper(),
            new PhpFpmHelper(),
            new Symfony2Helper(),
        );
    }

    /**
     * Set a helper set to be used with the command.
     *
     * @param HelperSet $helperSet The helper set
     */
    public function setHelperSet(HelperSet $helperSet)
    {
        $this->helperSet = $helperSet;
    }

    /**
     * Get the helper set associated with the command.
     *
     * @return HelperSet The HelperSet instance associated with this command
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * Gets a helper instance by name.
     *
     * @param string $name The helper name
     *
     * @return mixed The helper value
     *
     * @throws \InvalidArgumentException if the helper is not defined
     */
    public function getHelper($name)
    {
        return $this->helperSet->get($name);
    }

    public function setZoneName($name)
    {
        if (preg_match(
            '/[^a-z0-9_]/',
            $name
        )
        ) {
            throw new \Exception('Bad zone name. Only lower case characters and numbers are accepted.');
        }
        $this->id = $name;
    }

    public function getZoneName()
    {
        return $this->id;
    }

    public function setConfig(array $generalConfig, array $zonesConfig)
    {
        // Merge config
        $this->zonesConfig = $zonesConfig;
        $zoneConfig = $zonesConfig[$this->getZoneName()];
        $config = \array_replace_recursive($generalConfig, $zoneConfig);

        // Check required parameters
        if (empty($config['project'])) throw new \Exception('Project name not defined in project config parameter.');
        if (empty($config['environment'])) throw new \Exception('Environment not defined in environment config parameter.');
        if (empty($config['urls'])) throw new \Exception('Urls array not defined in urls config parameter.');
        if (empty($config['local_repository_dir'])) throw new \Exception('Local repository not defined in local_repository_dir config parameter.');
        if (empty($config['checkout_url'])) throw new \Exception('Checkout url not defined in default_checkout_url or zone checkout_url config parameter.');
        if (empty($config['checkout_branch'])) throw new \Exception('Checkout url not defined in default_checkout_branch or zone checkout_branch config parameter.');
        if (empty($config['repository_dir'])) throw new \Exception('Remote repository dir not defined in default_repository_dir or zone repository_dir config parameter.');
        if (empty($config['production_dir'])) throw new \Exception('Remote production dir not defined in default_repository_dir or zone production_dir config parameter.');
        if (empty($config['vcs'])) throw new \Exception('VCS not defined in config parameters.');
        if (empty($config['ssh'])) throw new \Exception('Ssh not defined in config parameters.');

        // Set config
        $this->project = $config['project'];
        $this->environment = $config['environment'];
        $this->urls = $config['urls'];
        $this->localRepositoryDir = $config['local_repository_dir'];
        $this->checkoutUrl = $config['checkout_url'];
        $this->checkoutBranch = $config['checkout_branch'];
        $this->remoteRepositoryDir = $config['repository_dir'];
        $this->remoteProductionDir = $config['production_dir'];
        $this->vcsType = $config['vcs'];
        if (!empty($config['checkout_proxy'])) $this->checkoutProxy = $config['checkout_proxy'];
        if (!empty($config['clean_max_deploys'])) $this->cleanMaxDeploys = $config['clean_max_deploys'];
        if (!empty($config['sudo'])) $this->sudo = $config['sudo'];
        if (!empty($config['helper'])) $this->helpersConfig = $config['helper'];

        // Save config. Useful for custom configs
        if (!empty($config['custom'])) $this->custom = $config['custom'];

        // get current version, running version
        $current_version_data_file = $this->getLocalDataCurrentVersionFile();
        if (file_exists($current_version_data_file))
            $this->currentVersion = file_get_contents($current_version_data_file);
        else
            $this->currentVersion = null;

        // get new version from file
        // if download a new version is get from remote
        $new_version_data_file = $this->getLocalDataNewVersionFile();
        if (file_exists($new_version_data_file))
            $this->newVersion = file_get_contents($new_version_data_file);
        else
            $this->newVersion = null;

        // vcs
        $this->createVcs();

        // ssh
        $this->setSshManager(new SshManager($config['ssh']));
        $this->sshConfig = $config['ssh'];

        // After define config, set deployer for helpers because now there are helpers configs
        $this->helperSet->setDeployer($this);
    }

    /**
     * @param boolean $force
     */
    public function setForce($force = true)
    {
        $this->force = $force;
    }

    /**
     * @return boolean
     */
    public function getForce()
    {
        return $this->force;
    }

    /**
     * @param mixed $urls
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }

    /**
     * @return mixed
     */
    public function getUrls()
    {
        return $this->urls;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->sshManager->setLogger($logger);
        $this->vcs->setLogger($logger);
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return \JordiLlonch\Bundle\DeployBundle\SSH\SshManager
     */
    public function getSshManager()
    {
        return $this->sshManager;
    }

    /**
     * @param \JordiLlonch\Bundle\DeployBundle\SSH\SshManager $sshManager
     */
    public function setSshManager($sshManager)
    {
        $this->sshManager = $sshManager;
    }

    /**
     * @return \JordiLlonch\Bundle\DeployBundle\VCS\VcsInterface
     */
    public function getVcs()
    {
        return $this->vcs;
    }

    /**
     * @param \JordiLlonch\Bundle\DeployBundle\VCS\VcsInterface $vcs
     */
    public function setVcs(VcsInterface $vcs)
    {
        $this->vcs = $vcs;
    }

    /**
     * @return mixed
     */
    public function getCustom()
    {
        return $this->custom;
    }

    public function getOtherZoneConfig($zoneName, $parameterName)
    {
        if (!isset($this->zonesConfig[$zoneName])) throw new \Exception('Zone name does not exists.');
        if (!isset($this->zonesConfig[$zoneName][$parameterName])) throw new \Exception('Zone name does not exists.');

        return $this->zonesConfig[$zoneName][$parameterName];
    }

    public function initialize()
    {
        $this->logger->debug('initialize');
        // initialize local repository directories if not exists
        $this->exec('mkdir -p "' . $this->getLocalCodeDir() . '"');
        $this->exec('mkdir -p "' . $this->getLocalDataDir() . '"');
        if (!is_dir($this->getLocalCodeDir()))
            throw new \Exception("Repository directories not exists: " . $this->getRemoteCodeDir());
        if (!is_dir($this->getLocalDataDir()))
            throw new \Exception("Repository directories not exists: " . $this->getDataDir());

        // initialize remote repository directories if not exists
        $sudo = $this->sudo ? 'sudo ' : '';
        $this->execRemoteServers($sudo . 'mkdir -p "' . $this->getRemoteCodeDir() . '"');
        $this->execRemoteServers($sudo . 'mkdir -p "' . $this->getRemoteSharedDir() . '"');
        $this->execRemoteServers($sudo . 'mkdir -p "' . $this->getRemoteBinDir() . '"');
        $this->execRemoteServers($sudo . 'chmod a+wrx "' . $this->getRemoteCodeDir() . '"');
        $this->execRemoteServers($sudo . 'chmod a+wrx "' . $this->getRemoteSharedDir() . '"');
        $this->execRemoteServers($sudo . 'chmod a+wrx "' . $this->getRemoteBinDir() . '"');
    }

    public function code2ProductionBefore()
    {
    }

    public function code2ProductionBeforeRollback()
    {
    }

    public function code2ProductionAfter()
    {
    }

    public function code2ProductionAfterRollback()
    {
    }

    public function getLocalRepositoryDir()
    {
        return $this->localRepositoryDir;
    }

    public function getLocalCodeDir()
    {
        return $this->getLocalRepositoryDir() . '/' . $this->getZoneName() . '/code';
    }

    public function getLocalDataDir()
    {
        return $this->getLocalRepositoryDir() . '/' . $this->getZoneName() . '/data';
    }

    public function getLocalDataCurrentVersionFile()
    {
        return $this->getLocalDataDir() . "/current_version";
    }

    public function getLocalDataNewVersionFile()
    {
        return $this->getLocalDataDir() . "/new_version";
    }

    public function getLocalNewRepositoryDir()
    {
        return $this->getLocalCodeDir() . '/' . $this->newVersion;
    }

    public function getLocalCurrentCodeDir()
    {
        return $this->getLocalCodeDir() . '/' . $this->currentVersion;
    }

    public function getRemoteRepositoryDir()
    {
        return $this->remoteRepositoryDir . '/' . $this->getZoneName();
    }

    public function getRemoteBinDir()
    {
        return $this->getRemoteRepositoryDir() . '/bin';
    }

    public function getRemoteSharedDir()
    {
        return $this->getRemoteRepositoryDir() . '/shared_code';
    }

    public function getRemoteProductionCodeDir()
    {
        return $this->remoteProductionDir;
    }

    public function getRemoteCodeDir()
    {
        return $this->getRemoteRepositoryDir() . '/code';
    }

    public function getRemoteCurrentRepositoryDir()
    {
        return $this->getRemoteCodeDir() . '/' . $this->currentVersion;
    }

    public function getRemoteNewRepositoryDir()
    {
        return $this->getRemoteCodeDir() . '/' . $this->newVersion;
    }

    /**
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return mixed
     */
    public function getHelpersConfig()
    {
        return $this->helpersConfig;
    }

    /**
     * @return boolean
     */
    public function getSudo()
    {
        return $this->sudo;
    }

    /**
     * @param $originPath
     * @param $targetPath
     * @param $rsyncParams
     * @throws \Exception
     */
    public function rsync2Servers($originPath, $targetPath, $rsyncParams = '')
    {
        foreach ($this->urls as $server) {
            try {
                $this->rsync($originPath, $server, $targetPath, $rsyncParams);
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * @param $originPath
     * @param $server
     * @param $serverPath
     * @param $rsyncParams
     */
    public function rsync($originPath, $server, $serverPath, $rsyncParams = '')
    {
        list($host, $port) = $this->extractHostPort($server);
        if ($host == 'localhost') $this->exec('cp -a "' . $originPath . '" "' . $serverPath . '"');
        else $this->exec('rsync -ar --delete -e "ssh -p ' . $port . ' -i \"' . $this->sshConfig['private_key_file'] . '\" -l ' . $this->sshConfig['user'] . ' -o \"UserKnownHostsFile=/dev/null\" -o \"StrictHostKeyChecking=no\"" --exclude ".git" ' . $rsyncParams . ' "' . $originPath . '" "' . $host . ':' . $serverPath . '"');
    }

    /**
     * @param $rsyncParams
     * @throws \Exception
     */
    public function syncronize($rsyncParams = '')
    {
        // Check if it is a new server to copy some old version in order to be able to rollback
        foreach ($this->urls as $server) {
            try {
                $this->syncronizeServer($server, $rsyncParams);
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    protected function downloadCodeVcs()
    {
        $this->logger->debug(__METHOD__);
        $this->vcs->cloneCodeRepository();
    }

    protected function getDiffFilesVcs($dirFrom, $dirTo)
    {
        return $this->vcs->getDiffFiles($dirFrom, $dirTo);
    }

    public function code2Servers($rsyncParams = '')
    {
        $this->logger->debug(__METHOD__);

        $this->syncronize($rsyncParams);
    }

    /**
     * Copy N old version to servers in order to be able to rollback
     * (discard the last version because it is version that it is downloaded and copied by code2Servers method)
     *
     * @param $server
     */
    public function syncronizeServer($server, $rsyncParams = '')
    {
        $this->logger->debug(__METHOD__);

        // Find all versions code in local
        $finder = new Finder();
        $finder->in($this->getLocalCodeDir());
        $finder->directories();
        $finder->sortByName();
        $finder->depth(0);
        $listLocalCodeDir = array();
        foreach ($finder as $file) $listLocalCodeDir[$file->getBasename()] = $file->getRealPath();

        // Find all versions code in remote server
        $this->logger->debug('remote dirs to sync: ');
        $r = $this->execRemoteServers('ls ' . $this->getRemoteCodeDir(), array($server));
        $listRemoteCodeDir = explode("\n", $r[$server]['output']);
        foreach ($listRemoteCodeDir as $key => $item) $listRemoteCodeDir[$key] = trim($item);

        $this->logger->debug('local dirs to sync: ' . print_r($listLocalCodeDir, true));
        $this->logger->debug('remote dirs to sync: ' . print_r($listRemoteCodeDir, true));

        // Sync
        foreach ($listLocalCodeDir as $basename => $realPath) {
            if(!in_array($basename, $listRemoteCodeDir)) {
                $this->rsync($realPath, $server, $this->getRemoteCodeDir(), $rsyncParams);
            }
        }
    }

    public function setNewVersion($new_version)
    {
        $this->logger->debug(__METHOD__ . ': ' . $new_version);

        $this->newVersionRollback = $this->newVersion;
        $this->newVersion = $new_version;
        file_put_contents($this->getLocalDataNewVersionFile(), $this->newVersion);

        // Set new destionation path to new destination
        $this->vcs->setDestinationPath($this->getLocalNewRepositoryDir());
    }

    public function setNewVersionRollback()
    {
        if (empty($this->newVersionRollback)) return;
        $this->logger->debug(__METHOD__ . ': ' . $this->newVersionRollback);
        $this->newVersion = $this->newVersionRollback;
        file_put_contents($this->getLocalDataNewVersionFile(), $this->newVersion);
    }

    public function setCurrentVersion($version)
    {
        $this->logger->debug(__METHOD__ . ': ' . $version);

        $this->currentVersion = $version;
        file_put_contents($this->getLocalDataCurrentVersionFile(), $this->currentVersion);
    }

    public function setCurrentVersionAsNewVersion()
    {
        $this->logger->debug(__METHOD__);
        $this->setCurrentVersion($this->newVersion);
    }

    public function setCurrentVersionRollback()
    {
        $this->logger->debug(__METHOD__ . ': ' . $this->currentVersionRollback);
        $this->currentVersion = $this->currentVersionRollback;
        file_put_contents($this->getLocalDataCurrentVersionFile(), $this->currentVersion);
    }

    public function runDownloadCode($newVersion)
    {
        // Check if deployer has been initialized
        if(!file_exists($this->getLocalRepositoryDir())) throw new \Exception('It seems deployer has not been initialized.');

        $vcsVersion = $this->vcs->getLastVersionFromRemote();
        $newVersion .= '_' . $vcsVersion;

        $this->logger->debug(__METHOD__ . ': ' . $newVersion);

        $this->setNewVersion($newVersion);
        $this->downloadCode();
    }

    public function runClean()
    {
        $this->clean();
    }

    public function runDownloadCodeRollback()
    {
        $this->logger->debug(__METHOD__);
        // rollback it is necessary
        if (empty($this->newVersionRollback)) return;

        try {
            $this->downloadCodeRollback();

            $this->exec('rm -rf "' . $this->getLocalNewRepositoryDir() . '"');
            $this->execRemoteServers('rm -rf "' . $this->getRemoteNewRepositoryDir() . '"');

        } catch (\Exception $e) {
        }

        $this->setNewVersionRollback();
    }

    public function runCode2Production($newRepositoryDir = null)
    {
        $this->logger->debug(__METHOD__);
        $this->currentVersionRollback = $this->currentVersion;
        $this->remoteRepositoryDirRollback = $this->getRemoteCurrentRepositoryDir();

        if (is_null($newRepositoryDir)) $newRepositoryDir = $this->getRemoteNewRepositoryDir();

        // update last version
        if (!$this->dryMode) {
            $this->logger->debug('code2ProductionBefore');
            $this->code2ProductionBefore();

            $productionCodeDir = $this->getRemoteProductionCodeDir();
            $this->atomicChangeOfCode2Production($newRepositoryDir, $productionCodeDir);
            $this->logger->debug('clear cache');
            $this->runClearCache();

            $this->setCurrentVersionAsNewVersion();

            $this->pushLastDeployTag();

            $this->logger->debug('code2ProductionAfter');
            $this->code2ProductionAfter();
        }
    }

    public function runCode2ProductionRollback()
    {
        $this->logger->debug(__METHOD__);
        // rollback it is necessary
        if (empty($this->currentVersionRollback)) return;

        try {
            if (!$this->dryMode) {
                $this->logger->debug('code2ProductionBeforeRollback');
                $this->code2ProductionBeforeRollback();

                // restore symbolics links
                $productionCodeDir = $this->getRemoteProductionCodeDir();
                $rollbackRepositoryDir = $this->remoteRepositoryDirRollback;
                $this->atomicRollbackChangeCode2Production($rollbackRepositoryDir, $productionCodeDir);
                $this->logger->debug('clear cache');
                $this->runClearCache();

                $this->pushLastDeployTag($rollbackRepositoryDir);

                $this->logger->debug('code2ProductionAfterRollback');
                $this->code2ProductionAfterRollback();
            }
        } catch (\Exception $e) {
        }

        $this->setCurrentVersionRollback();
    }

    abstract protected function runClearCache();

    public function exec($command, &$output = null)
    {
        $this->logger->debug('exec: ' . $command);

        if ($this->dryMode) return;

        $outputLastLine = exec($command, $output, $returnVar);
        if ($returnVar != 0) throw new \Exception('ERROR executing: ' . $command . "\n" . implode("\n", $output));

        if(!empty($output)) foreach($output as $item) $this->logger->debug('exec output: ' . $item);

        return $outputLastLine;
    }

    protected function execRemote(array $servers, $command)
    {
        if ($this->dryMode) {
            $r = array();
            foreach($servers as $server) {
                $commandMsg = '[' . $server . ']: ' . $command . ' (dryMode)';
                $this->logger($commandMsg);
                $this->output->writeln($commandMsg);
                $r[$server]['exit_code'] = 0;
                $r[$server]['output'] = '';
                $r[$server]['error'] = '';
            }
        }
        else {
            $r = $this->sshManager->exec($servers, $command);

            // Check errors
            $errors = array();
            foreach ($r as $server => $item) {
                if($item['exit_code'] != 0) $errors[] = 'Error on server ' . $server . ': ' . $item['error'];
            }
            if(count($errors)) throw new \Exception(implode("\n", $errors));
        }

        // log output
        foreach($r as $server => $item) {
            $this->logger->debug('exec exit_code: ' . $item['exit_code']);
            if(!empty($item['output'])) $this->logger->debug('exec output: ' . $item['output']);
            if(!empty($item['error'])) $this->logger->debug('exec error: ' . $item['error']);
        }

        return $r;
    }

    public function execRemoteServers($command, $urls = null)
    {
        if (is_null($urls)) $urls = $this->urls;
        $r = $this->execRemote($urls, $command);

        return $r;
    }

    /**
     * Execute command to current host and all servers in config
     * @param string $command
     */
    public function execAll($command)
    {
        $this->logger->debug(__METHOD__);
        $this->exec($command);
        $this->execRemoteServers($command);
    }

    public function clean()
    {
        $this->logger->debug('Cleaning old deploys...');

        $finder = new Finder();
        $finder->in($this->getLocalCodeDir());
        $finder->directories();
        $finder->sortByName();
        $finder->depth(0);
        $directoryList = array();
        foreach ($finder as $file) $directoryList[] = $file->getBaseName();

        $sudo = $this->sudo ? 'sudo ' : '';
        while(count($directoryList) > $this->cleanMaxDeploys) {
            $path = array_shift($directoryList);
            $this->exec('rm -rf ' . $this->getLocalCodeDir() . '/' . $path);
            $this->execRemoteServers($sudo. 'rm -rf ' . $this->getRemoteCodeDir() . '/' . $path);
        }
    }

    public function setCleanMaxDeploys($maxDeploys)
    {
        $this->cleanMaxDeploys = $maxDeploys;
    }

    public function runRollback($version)
    {
        $this->logger->debug(__METHOD__);

        // $version could be a negative integer to step backward or a concrete version
        if(is_numeric($version)) {
            $versionStep = $version;
            $arrListDir = $this->getVersionDirList(false);
            $keyCurrentVersion = array_search($this->currentVersion, $arrListDir);
            if($keyCurrentVersion === false) throw new \Exception('Current version is not found in the available versions list.');
            $versionNum = $keyCurrentVersion + $versionStep;
            $arrListDirValues = array_values($arrListDir);
            if(!isset($arrListDirValues[$versionNum])) throw new \Exception('There are only ' . count($arrListDir) . ' available versions to step backward.');
            $version = $arrListDirValues[$versionNum];
        }

        // $newVersion exists?
        $arrListDir = $this->getVersionDirList();
        if(!in_array($version, $arrListDir)) throw new \Exception($version . ' version not found.' . "\n");

        // Do changes
        $versionBak = $this->newVersion;
        $this->logger->debug('rolling back to version: ' . $version);
        $this->rollingBackFromVersion = $this->currentVersion;
        $this->rollingBackToVersion = $version;
        $this->rollingBack = true;
        $this->setNewVersion($version);
        $this->runCode2Production();
        $this->setNewVersion($versionBak);
        $this->rollingBack = false;
    }

    public function getRollbackList()
    {
        $this->logger->debug(__METHOD__);
        
        $arrListDir = $this->getVersionDirList();
        $r = array();
        foreach($arrListDir as $item) {
            list($dateRaw, $hourRaw, $hash) = explode('_', $item);
            $date = new \DateTime();
            $date->setDate(substr($dateRaw, 0, 4), substr($dateRaw, 4, 2), substr($dateRaw, 6, 2));
            $date->setTime(substr($hourRaw, 0, 2), substr($hourRaw, 2, 2), substr($hourRaw, 4, 2));
            $newItem = array(
                'date' => $date,
                'hash_small' => substr($hash, 0, 5),
                'hash' => $hash,
            );
            $r[$item] = $newItem;
        }

        return $r;
    }
        
    public function getStatus()
    {
        $r = array(
            "id" => $this->getZoneName(),
            "current_version" => $this->currentVersion,
            "new_version" => $this->newVersion,
        );

        return $r;
    }

    protected function filterText($messages, $toDelete, $extraLines = null)
    {
        $lineToDelete = array();
        foreach ($messages as $i => $line) {
            foreach ($toDelete as $pattern) {
                if (preg_match($pattern, $line)) {
                    $lineToDelete[] = $i;
                    if (is_array($extraLines))
                        foreach ($extraLines as $lineNumber)
                            $lineToDelete[] = $i + $lineNumber;
                }
            }
        }

        $r = array();
        foreach ($messages as $i => $line)
            if (!in_array($i, $lineToDelete))
                $r[] = $line;

        return $r;
    }

    protected function getTargetDeployLastTag()
    {
        return 'deployer_last_' . $this->environment . '_' . $this->project . '_' . $this->getZoneName();
    }

    protected function extractConfigs($data)
    {
        $r = array();
        foreach ($data as $i => $line) {
            if (preg_match('/config/', $line)) $r[] = $line;
            else if (preg_match('/schema/', $line)) $r[] = $line;
        }

        return $r;
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

    /**
     * @param $newRepositoryDir
     * @param $productionCodeDir
     */
    protected function atomicChangeOfCode2Production($newRepositoryDir, $productionCodeDir)
    {
        $this->logger->debug('create symbolic link');
        $sudo = $this->sudo ? 'sudo ' : '';
        $this->execRemoteServers($sudo . 'ln -sfn ' . $newRepositoryDir . ' ' . $productionCodeDir);
    }

    /**
     * @param $rollbackRepositoryDir
     * @param $productionCodeDir
     */
    protected function atomicRollbackChangeCode2Production($rollbackRepositoryDir, $productionCodeDir)
    {
        $this->logger->debug('restore symbolic link');
        $sudo = $this->sudo ? 'sudo ' : '';
        $this->execRemoteServers($sudo . 'ln -sfn ' . $rollbackRepositoryDir . ' ' . $productionCodeDir);
    }

    /**
     * Push tag that set last deploy point to the origin repository in order to know which is the last commit deployed
     */
    protected function pushLastDeployTag($newRepositoryDir = null)
    {
        if(is_null($newRepositoryDir)) $this->vcs->setDestinationPath($this->getLocalNewRepositoryDir());

        //$this->vcs->pushLastDeployTag($this->getTargetDeployLastTag(), $newRepositoryDir);
    }

    protected function getHeadHash($repositoryDir = null)
    {
        $this->logger->debug(__METHOD__);

        $hash = $this->vcs->getHeadHash($repositoryDir);

        return $hash;
    }

    /**
     * Get from-to hash between two repositories
     * @return array($hashFrom, $hashTo)
     */
    public function getHashFromCurrentCodeToNewRepository()
    {
        $repoDirFrom = $this->getLocalCurrentCodeDir();
        $repoDirTo = $this->getLocalNewRepositoryDir();

        return $this->getHashFromTo($repoDirFrom, $repoDirTo);
    }

    protected function getHashFromTo($repoDirFrom, $repoDirTo)
    {
        $hashFrom = $this->getHeadHash($repoDirFrom);
        $hashTo = $this->getHeadHash($repoDirTo);

        return array($hashFrom, $hashTo);
    }

    /**
     * List of current downloaded versions of code
     * First last available version (without current)
     *
     * @return array
     */
    protected function getVersionDirList($removeCurrentVersion = true)
    {
        // Get directory version list
        $dir = new \DirectoryIterator($this->getLocalCodeDir());
        $arrListDir = array();
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isDir()) // also check if directory is the current one
            {
                $arrListDir[$fileinfo->__toString()] = $fileinfo->__toString();
            }
        }

        // Remove current version
        if($removeCurrentVersion) {
            $currentVersion = $this->currentVersion;
            $arrListDir = array_filter($arrListDir, function($item) use($currentVersion) { return $item != $currentVersion; });
        }

        krsort($arrListDir);
        $arrListDir = array_values($arrListDir);

        return $arrListDir;
    }

    /**
     * @param $branch
     */
    public function updateBranch($branch)
    {
        $this->checkoutBranch = $branch;
        $this->createVcs();
    }

    protected function createVcs()
    {
        $vcsFactory = new VcsFactory($this->checkoutUrl, $this->checkoutBranch, $this->checkoutProxy, $this->dryMode);
        $this->setVcs($vcsFactory->create($this->vcsType));
        if ($this->getLogger()) $this->getVcs()->setLogger($this->getLogger());
    }
}
