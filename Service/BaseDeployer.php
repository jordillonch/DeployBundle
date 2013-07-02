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

use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

abstract class BaseDeployer
{
    protected $project;
    protected $environment;
    protected $mailFrom = "iamrobot@me.com";
    protected $mailTo;
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
    protected $cleanBeforeDays = 7;
    protected $dryMode = false;
    protected $newVersion;
    protected $currentVersion;
    protected $output;
    protected $sudo = false;
    protected $numOldVersionsToCopy = 3;
    protected $custom;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    protected $rollingBack = false;
    protected $rollingBackFromVersion;
    protected $rollingBackToVersion;
    protected $zonesConfig;

    public function __construct()
    {
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
//        $this->id = strtolower( preg_replace( array( '/[^-a-zA-Z0-9\s]/', '/[\s]/' ), array( '', '-' ), $name ) );
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
        $config = \array_merge($generalConfig, $zoneConfig);

        // Check required parameters
        if (empty($config['project'])) throw new \Exception('Project name not defined in project config parameter.');
        if (empty($config['environment'])) throw new \Exception('Environment not defined in environment config parameter.');
        if (empty($config['urls'])) throw new \Exception('Urls array not defined in urls config parameter.');
        if (empty($config['local_repository_dir'])) throw new \Exception('Local repository not defined in local_repository_dir config parameter.');
        if (empty($config['checkout_url'])) throw new \Exception('Checkout url not defined in default_checkout_url or zone checkout_url config parameter.');
        if (empty($config['checkout_branch'])) throw new \Exception('Checkout url not defined in default_checkout_branch or zone checkout_branch config parameter.');
        if (empty($config['repository_dir'])) throw new \Exception('Remote repository dir not defined in default_repository_dir or zone repository_dir config parameter.');
        if (empty($config['production_dir'])) throw new \Exception('Remote production dir not defined in default_repository_dir or zone production_dir config parameter.');

        // Set config
        $this->project = $config['project'];
        $this->environment = $config['environment'];
        $this->mailFrom = $config['mail_from'];
        $this->mailTo = $config['mail_to'];
        $this->urls = $config['urls'];
        $this->localRepositoryDir = $config['local_repository_dir'];
        $this->checkoutUrl = $config['checkout_url'];
        $this->checkoutBranch = $config['checkout_branch'];
        $this->remoteRepositoryDir = $config['repository_dir'];
        $this->remoteProductionDir = $config['production_dir'];
        if (!empty($config['checkout_proxy'])) $this->checkoutProxy = $config['checkout_proxy'];
        if (!empty($config['clean_before_days'])) $this->cleanBeforeDays = $config['clean_before_days'];
        if (!empty($config['sudo'])) $this->sudo = $config['sudo'];

        // Save config. Useful for custom configs
        $this->custom = $config['custom'];

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
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function getOtherZoneConfig($zoneName, $parameterName)
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
            throw new \Exception("Repository directories do not exists: " . $this->getRemoteCodeDir());
        if (!is_dir($this->getLocalDataDir()))
            throw new \Exception("Repository directories do not exists: " . $this->getDataDir());

        // initialize remote repository directories if not exists
        $sudo = $this->sudo ? 'sudo ' : '';
        $this->execRemoteServers($sudo . 'mkdir -p "' . $this->getRemoteCodeDir() . '"');
        $this->execRemoteServers($sudo . 'mkdir -p "' . $this->getRemoteSharedDir() . '"');
        $this->execRemoteServers($sudo . 'mkdir -p "' . $this->getRemoteBinDir() . '"');
        $this->execRemoteServers($sudo . 'chmod a+wrx "' . $this->getRemoteCodeDir() . '"');
        $this->execRemoteServers($sudo . 'chmod a+wrx "' . $this->getRemoteSharedDir() . '"');
        $this->execRemoteServers($sudo . 'chmod a+wrx "' . $this->getRemoteBinDir() . '"');
    }

    abstract public function downloadCode();

    abstract public function downloadCodeRollback();

    protected function code2ProductionBefore()
    {
    }

    protected function code2ProductionBeforeRollback()
    {
    }

    protected function code2ProductionAfter()
    {
    }

    protected function code2ProductionAfterRollback()
    {
    }

    protected function getLocalRepositoryDir()
    {
        return $this->localRepositoryDir;
    }

    protected function getLocalCodeDir()
    {
        return $this->localRepositoryDir . '/' . $this->id . '/code';
    }

    protected function getLocalDataDir()
    {
        return $this->localRepositoryDir . '/' . $this->id . '/data';
    }

    protected function getLocalDataCurrentVersionFile()
    {
        return $this->getLocalDataDir() . "/current_version";
    }

    protected function getLocalDataNewVersionFile()
    {
        return $this->getLocalDataDir() . "/new_version";
    }

    protected function getLocalNewRepositoryDir()
    {
        return $this->getLocalCodeDir() . '/' . $this->newVersion;
    }

    protected function getLocalCurrentCodeDir()
    {
        return $this->getLocalCodeDir() . '/' . $this->currentVersion;
    }

    protected function getRemoteRepositoryDir()
    {
        return $this->remoteRepositoryDir . '/' . $this->id;
    }

    protected function getRemoteBinDir()
    {
        return $this->getRemoteRepositoryDir() . '/bin';
    }

    protected function getRemoteSharedDir()
    {
        return $this->getRemoteRepositoryDir() . '/shared_code';
    }

    protected function getRemoteProductionCodeDir()
    {
        return $this->remoteProductionDir;
    }

    protected function getRemoteCodeDir()
    {
        return $this->getRemoteRepositoryDir() . '/code';
    }

    protected function getRemoteCurrentRepositoryDir()
    {
        return $this->getRemoteCodeDir() . '/' . $this->currentVersion;
    }

    protected function getRemoteNewRepositoryDir()
    {
        return $this->getRemoteCodeDir() . '/' . $this->newVersion;
    }

    protected function downloadCodeGit()
    {
        $this->logger->debug(__METHOD__);

        // Update repo if it is a proxy of a remote repo
        if ($this->checkoutProxy) {
            $urlParsed = parse_url($this->checkoutUrl);
            $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" pull');
        }

        // Clone repo
        $this->exec('git clone "' . $this->checkoutUrl . '" "' . $this->getLocalNewRepositoryDir() . '" --branch "' . $this->checkoutBranch . '" --depth=1');

        // Overwrite origin remote if it is a proxy
        if ($this->checkoutProxy) {
            $originUrlProxyRepo = $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" config --get remote.origin.url');
            $this->exec('git --git-dir="' . $this->getLocalNewRepositoryDir() . '/.git" config --replace-all remote.origin.url "' . $originUrlProxyRepo . '"');
        }
    }

    protected function getDiffFilesGit($gitDirFrom, $gitDirTo)
    {
        $gitUidFrom = $this->getHeadHash($gitDirFrom);
        $gitUidTo = $this->getHeadHash($gitDirTo);
        if ($gitUidFrom && $gitUidFrom) {
            exec('git --git-dir="' . $gitDirTo . '/.git" diff ' . $gitUidTo . ' ' . $gitUidFrom . ' --name-only', $diffFiles);

            return $diffFiles;
        }

        return array();
    }

    public function code2Servers($rsync_params = '')
    {
        $this->logger->debug(__METHOD__);

        $newRepositoryDir = $this->getLocalNewRepositoryDir();
        $code_dir = $this->getRemoteCodeDir();
        foreach ($this->urls as $server) {
            try {
                list($host, $port) = $this->extractHostPort($server);

                // Check if it is a new server to copy some old version in order to be able to rollback
                if($this->isNewServer($server)) $this->copyOldVersions($server, $this->numOldVersionsToCopy, $rsync_params);

                // Copy code
                $this->exec(
                    'rsync -ar --delete -e "ssh -p ' . $port . ' -o \"UserKnownHostsFile=/dev/null\" -o \"StrictHostKeyChecking=no\"" ' . $rsync_params . ' "' . $newRepositoryDir . '" "' . $host . ':' . $code_dir . '"'
                );
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Check if it is a new server looking how many downloaded version there is
     *
     * @param $server
     */
    protected function isNewServer($server)
    {
        $r = $this->execRemoteServers('ls ' . $this->getRemoteCodeDir(), array($server));

        if(empty($r[$server])) return true;
        else return false;
    }

    /**
     * Copy N old version to servers in order to be able to rollback
     * (discard the last version because it is version that it is downloaded and copied by code2Servers method)
     *
     * @param $server
     * @param int $numOldVersionsToCopy
     */
    protected function copyOldVersions($server, $numOldVersionsToCopy = 3, $rsync_params = '')
    {
        $this->logger->debug(__METHOD__);

        // Find all versions code sorted by name (oldest first)
        $finder = new Finder();
        $finder->in($this->getLocalCodeDir());
        $finder->directories();
        $finder->sortByName();
        $finder->depth(0);
        $directoryList = array();
        foreach ($finder as $file) $directoryList[] = $file->getRealPath();

        // Copy N versions to servers
        $code_dir = $this->getRemoteCodeDir();
        list($host, $port) = $this->extractHostPort($server);
        $c = count($directoryList);
        for($i=$c-2; $i>=0 && $i>=$c-1-$numOldVersionsToCopy; $i--) {
            $directoryToCopy = $directoryList[$i];
            // Copy code
            $this->exec('rsync -ar --delete -e "ssh -p ' . $port . ' -o \"UserKnownHostsFile=/dev/null\" -o \"StrictHostKeyChecking=no\"" ' . $rsync_params . ' "' . $directoryToCopy . '" "' . $host . ':' . $code_dir . '"');
        }
    }

    public function setNewVersion($new_version)
    {
        $this->logger->debug(__METHOD__ . ': ' . $new_version);

        $this->new_version_rollback = $this->newVersion;
        $this->newVersion = $new_version;
        file_put_contents($this->getLocalDataNewVersionFile(), $this->newVersion);
    }

    public function setNewVersionRollback()
    {
        if (empty($this->new_version_rollback)) return;
        $this->logger->debug(__METHOD__ . ': ' . $this->new_version_rollback);
        $this->newVersion = $this->new_version_rollback;
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
        $this->logger->debug(__METHOD__ . ': ' . $this->current_version_rollback);
        $this->currentVersion = $this->current_version_rollback;
        file_put_contents($this->getLocalDataCurrentVersionFile(), $this->currentVersion);
    }

    public function runDownloadCode($new_version)
    {
        // get last version from remote
        if($this->checkoutProxy)
        {
            $urlParsed = parse_url($this->checkoutUrl);
            $git_versions = $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" ls-remote origin ' . $this->checkoutBranch);
        }
        else $git_versions = $this->exec('git ls-remote "' . $this->checkoutUrl . '" origin ' . $this->checkoutBranch);

        $git_versions = explode("\n", $git_versions);
        if (!isset($git_versions[0])) throw new \Exception("Git repository empty.");
        $git_version = '';
        foreach ($git_versions as $item) if (\preg_match('/' . $this->checkoutBranch . '/', $item)) $git_version = $item;
        if (empty($git_version)) throw new \Exception("Git branch " . $this->checkoutBranch . " not found.");
        $git_version = explode("\t", $git_version);
        $git_version = $git_version[0];
        if (empty($git_version)) throw new \Exception("Unable to get last git version.");
        $new_version .= '_' . $git_version;

        $this->logger->debug(__METHOD__ . ': ' . $new_version);

        $this->setNewVersion($new_version);
        $this->downloadCode();
        //$this->clean();
    }

    public function runClean()
    {
        $this->clean();
    }

    public function runDownloadCodeRollback()
    {
        $this->logger->debug(__METHOD__);
        // rollback it is necessary
        if (empty($this->new_version_rollback)) return;

        try {
            $this->downloadCodeRollback();

            $this->exec('rm -rf "' . $this->getLocalNewRepositoryDir() . '"');
            $this->execRemoteServers('rm -rf "' . $this->getRemoteNewRepositoryDir() . '"');

        } catch (\Exception $e) {
        }

        $this->setNewVersionRollback();
    }

    public function runCode2Production($newRepositoryDir = null, $new_version = null)
    {
        $this->logger->debug(__METHOD__);
        $this->current_version_rollback = $this->currentVersion;
        $this->remoteRepositoryDirRollback = $this->getRemoteCurrentRepositoryDir();

        if ($newRepositoryDir == null) $newRepositoryDir = $this->getRemoteNewRepositoryDir();
        if ($new_version == null) $new_version = $this->newVersion;

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
        if (empty($this->current_version_rollback)) return;

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

    protected function exec($command)
    {
        $this->logger->debug('exec: ' . $command);

        if ($this->dryMode) return;

        $r = exec($command, $output, $return_var);
        if ($r === false || $return_var != 0) throw new \Exception('ERROR executing: ' . $command . "\n" . $r);

        return $r;
    }

    protected function execRemote(array $servers, $command)
    {
        $command = str_replace('"', '\\"', $command);
        $r = array();
        foreach ($servers as $server) {
            $r[$server] = null;
            list($host, $port) = $this->extractHostPort($server);
            if ($host == 'localhost') $sshCommand = $command;
            else $sshCommand = 'ssh -t -p ' . $port . ' -o "LogLevel=quiet" -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" ' . $host . ' "' . $command . '"';
            if ($this->dryMode) $this->output->writeln($sshCommand);
            else $r[$server] = $this->exec($sshCommand);
        }

        return $r;
    }

    protected function execRemoteServers($command, $urls = null)
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

    public function mkdirRecursive($pathname, $mode)
    {
        return @mkdir($pathname, $mode, true);
    }

    public function clean()
    {
        $this->logger->debug('clean old code');
        $sudo = $this->sudo ? 'sudo ' : '';
        $dir = new \DirectoryIterator($this->getLocalCodeDir());
        $count = \iterator_count($dir);
        $firstTextOut = false;
        foreach ($dir as $fileinfo) {
            if ($count <= 4)
                break; // left a minimum of 4 items (., .., current repo and a bak one repo)

            if (!$fileinfo->isDot() && $fileinfo->isDir() && $fileinfo != basename(
                    $this->getRemoteCurrentRepositoryDir()
                )
            ) // also check if directory is the current one
            {
                $arr_info = explode("_", $fileinfo);
                if (count($arr_info) != 3)
                    continue;
                list($date, $time) = $arr_info;
                if ($fileinfo->getCTime() < time() - ($this->cleanBeforeDays * 24 * 3600)) {
                    $this->logger->debug('removing: ' . $this->getLocalCodeDir() . '/' . $fileinfo);
                    if(!$firstTextOut) $this->output->writeln('<info>Removing old code...</info>');
                    $firstTextOut = true;
                    $this->exec($sudo . 'rm -rf ' . $this->getLocalCodeDir() . '/' . $fileinfo);
                    $this->execRemoteServers($sudo. 'rm -rf ' . $this->getRemoteCodeDir() . '/' . $fileinfo);
                }
            }
            $count--;
        }
    }

    public function setCleanBeforeDays($days)
    {
        $this->cleanBeforeDays = $days;
    }

    public function runRollback()
    {
        $this->logger->debug(__METHOD__);
        // get directory version list
        $dir = new \DirectoryIterator($this->getLocalCodeDir());
        $arr_list_dir = array();
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isDir()) // also check if directory is the current one
            {
                $arr_list_dir[$fileinfo->getCTime()] = $fileinfo->__toString();
            }
        }
        ksort($arr_list_dir);
        // get previous version
        while (count($arr_list_dir) && array_pop($arr_list_dir) != $this->currentVersion) {
        }
        if (count($arr_list_dir) == 0) throw new \Exception('Previous version not found.' . "\n");
        $new_version = array_pop($arr_list_dir);
        // do changes
        $new_version_bak = $this->newVersion;
        $this->logger->debug('rolling back to version: ' . $new_version);
        $this->rollingBackFromVersion = $this->currentVersion;
        $this->rollingBackToVersion = $new_version;
        $this->rollingBack = true;
        $this->setNewVersion($new_version);
        $this->runCode2Production();
        $this->setNewVersion($new_version_bak);
        $this->rollingBack = false;
    }

    public function getStatus()
    {
        $r = array(
            "id" => $this->id,
            "current_version" => $this->currentVersion,
            "new_version" => $this->newVersion,
        );

        return $r;
    }

    protected function filterText($messages, $to_delete, $extra_lines = null)
    {
        $line_to_delete = array();
        foreach ($messages as $i => $line) {
            foreach ($to_delete as $pattern) {
                if (preg_match($pattern, $line)) {
                    $line_to_delete[] = $i;
                    if (is_array($extra_lines))
                        foreach ($extra_lines as $line_number)
                            $line_to_delete[] = $i + $line_number;
                }
            }
        }

        $r = array();
        foreach ($messages as $i => $line)
            if (!in_array($i, $line_to_delete))
                $r[] = $line;

        return $r;
    }

    protected function code2ProductionAfterSendMail()
    {
        if (!$this->rollingBack) {
            $this->sendMailDiffs();
        } else {
            $this->sendMailRollback();
        }
    }

    protected function sendMailDiffs()
    {
        // git: extreu diffs
        $git_messages = $this->getGitDiffsCommitMessages();
        $git_files = $this->getGitDiffsFiles();

        $to_delete = array(
            '/push deploy /',
            "/Merge remote\-tracking branch \'origin\/master\' into deploy\-/"
        );
        $extra_lines = array(-1, -2, -3, -4, 1);
        $git_messages = $this->filterText($git_messages, $to_delete, $extra_lines);
        $to_delete = array(
            '/^cache/',
            '/data\/sql\/schema\.sql/'
        );
        $git_files = $this->filterText($git_files, $to_delete);
        $git_messages_formated = $this->formatSummaryMessages($git_messages);
        $git_files_formated = $this->formatDiffFiles($git_files);

        $git_config_files = $this->extractConfigs($git_files);
        $git_migration_files = $this->extractMigrations($git_files);

        // text message
        $body = "DIFFS\n";
        $body .= "=====\n";
        $body .= "Messages:\n";
        $body .= "---------\n";
        $body .= implode("\n", $git_messages);
        $body .= "\n\n";
        $body .= "Diff files:\n";
        $body .= "-----------\n";
        $body .= implode("\n", $git_files);
        $body .= "\n\n";
        if (count($git_messages) && count($git_files)) echo $body;

        // mail message
        $body_html = "<html>\n";
        $body_html .= "<body>\n";
        $body_html .= "<h1>DIFFS</h1>\n";
        $body_html .= "<h2>Summary:</h2>\n";
        $body_html .= "<p>" . implode("", $git_messages_formated) . "</p>";
        $body_html .= "<br/>\n";
        if (count($git_config_files)) {
            $git_config_files_formated = $this->formatDiffFiles($git_config_files);
            $body_html .= "<h2>Config files:</h2>\n";
            $body_html .= "<p>" . implode("<br/>\n", $git_config_files_formated) . "</p>";
            $body_html .= "<br/>\n";
        }
        if (count($git_migration_files)) {
            $git_migration_files_formated = $this->formatDiffFiles($git_migration_files);
            $body_html .= "<h2>Migration files:</h2>\n";
            $body_html .= "<p>" . implode("<br/>\n", $git_migration_files_formated) . "</p>";
            $body_html .= "<br/>\n";
        }
        $body_html .= "<h2>Diff files:</h2>\n";
        $body_html .= "<p>" . implode("<br/>\n", $git_files_formated) . "</p>";
        $body_html .= "<br/>\n";
        $body_html .= "<h2>Messages:</h2>\n";
        $body_html .= "<p>" . implode("<br/>\n", $git_messages_formated) . "</p>";
        $body_html .= "<br/>\n";
        $body_html .= "</body>\n";
        $body_html .= "</html>\n\n";

        // envia mail
        if (count($git_messages) && count($git_files)) {
            $mails = $this->mailTo;
            foreach ($mails as $mail) {
                $subject = 'Deploy ' . $this->environment . '_' . $this->project . ' - ' . $this->id;
                $headers = 'MIME-Version: 1.0' . "\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\n";
                $headers .= 'From: Deploy Robot <' . $this->mailFrom . ">\n";
                $headers .= 'Reply-To: Deploy Robot <' . $this->mailFrom . ">\n";
                $headers .= 'X-Mailer: PHP/' . phpversion();

                $to = $mail;
                $r = mail($to, $subject, $body_html, $headers);
                if (!$r) echo 'MAIL NOT SENT.' . "\n";
            }
        }

//        $this->pushLastDeployTag();
    }

    protected function sendMailRollback()
    {
        $version_from = $this->rollingBackFromVersion;
        $version_to = $this->rollingBackToVersion;

        $body_html = "<html>\n";
        $body_html .= "<body>\n";
        $body_html .= "<h1>ROLLBACK</h1>\n";
        $body_html .= '<table border="0" padding="1">';
        $body_html .= "<tr><td>From version:</td><td>$version_from</td></tr>";
        $body_html .= "<tr><td>To version:</td><td>$version_to</td></tr>";
        $body_html .= "<table/>\n";
        $body_html .= "</body>\n";
        $body_html .= "</html>\n\n";

        $mails = $this->mailTo;
        foreach ($mails as $mail) {
            $subject = 'Rollback ' . $this->environment . '_' . $this->project . ' - ' . $this->id;
            $headers = 'MIME-Version: 1.0' . "\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\n";
            $headers .= 'From: Deploy Robot <' . $this->mailFrom . ">\n";
            $headers .= 'Reply-To: Deploy Robot <' . $this->mailFrom . ">\n";
            $headers .= 'X-Mailer: PHP/' . phpversion();

            $to = $mail;
            $r = mail($to, $subject, $body_html, $headers);
            if (!$r) echo 'MAIL NOT SENT.' . "\n";
        }
    }

    protected function getGitDiffsCommitMessages()
    {
        $newRepositoryDir = $this->getLocalNewRepositoryDir();
        exec('git --git-dir="' . $newRepositoryDir . '/.git" log ' . $this->getTargetDeployLastTag() . '..HEAD', $r);

        return $r;
    }

    protected function getGitDiffsFiles()
    {
        $newRepositoryDir = $this->getLocalNewRepositoryDir();
        $exec = 'git --git-dir="' . $newRepositoryDir . '/.git" diff --name-only ' . $this->getTargetDeployLastTag() . ' HEAD';
        exec($exec, $r);

        return $r;
    }

    protected function getTargetDeployLastTag()
    {
        return 'deployer_last_' . $this->environment . '_' . $this->project . '_' . $this->id;
    }

    protected function formatSummaryMessages($data)
    {
        $r = array();
        $r[] = '<table border="0" padding="1" width="100%">' . "\n";
        foreach ($data as $i => $line) {
            if (preg_match('/^Author\: (.*)/', $line, $matches)) {
                $offset = 0;
                if (preg_match('/^Merge/', $data[$i - 1])) $offset = -1;
                $author = trim($matches[1]);
                $commit = trim(str_replace('commit ', '', $data[$i - 1 + $offset]));
                $date = date('Y-m-d H:i', strtotime(trim(str_replace('Date: ', '', $data[$i + 1]))));
                $message = $data[$i + 3];
                $r[] = '<tr><td width="15%"><a href="http://git.me.com/gitweb/?p=me;a=commit;h=' . $commit . '">' . htmlspecialchars(
                        $date
                    ) . '</a></td><td width="15%">' . htmlspecialchars(
                        $author
                    ) . '</td><td width="70%">' . htmlspecialchars($message) . '</td></tr>' . "\n";
            }
        }
        $r[] = '</table>' . "\n";

        return $r;
    }

    protected function formatDiffFiles($data)
    {
        $r = array();
        foreach ($data as $i => $line) {
            $r[] = '<a href="http://git.me.com/gitweb/?p=me;a=blob;hb=HEAD;f=' . $line . '">' . htmlspecialchars(
                    $line
                ) . '</a>';
        }

        return $r;
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

    protected function extractMigrations($data)
    {
        $r = array();
        foreach ($data as $i => $line) {
            if (preg_match('/lib\/migration\/doctrine\//', $line)) $r[] = $line;
        }

        return $r;
    }

    public function sendWarningNDaysDeploy($optionSendWarningNDaysDeploy)
    {
        // check
        $status = $this->getStatus();
        $current_version = $status['current_version'];
        list($date) = explode('_', $current_version);
        $date = new \DateTime($date);
        $interval = $date->diff(new \DateTime('now'));
        if ($interval->format('%a') >= $optionSendWarningNDaysDeploy) // send warning
        {
            $body_html = "<html>\n";
            $body_html .= "<body>\n";
            $body_html .= "<h1>OLD DEPLOY [{$this->id}]</h1>\n";
            $body_html .= '<table border="0" padding="1">';
            $body_html .= "<tr><td>Current version:</td><td>$current_version</td></tr>";
            $body_html .= "<table/>\n";
            $body_html .= "</body>\n";
            $body_html .= "</html>\n\n";

            $mails = $this->mailTo;
            foreach ($mails as $mail) {
                $subject = 'Old code ' . $this->environment . '_' . $this->project . ' - ' . $this->id;
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                $headers .= 'From: ' . $this->mailFrom . "\r\n";
                $headers .= 'Reply-To: ' . $this->mailFrom . "\r\n";
                $headers .= 'X-Mailer: PHP/' . phpversion();
                $to = $mail;
                $r = mail($to, $subject, $body_html, $headers);
                if (!$r) echo 'MAIL NOT SENT.' . "\n";
            }
        }

    }

    protected function filesReplacePattern(array $paths, $pattern, $replacement)
    {
        $errors = array();
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                $error = 'File "' . $path . '" does not exists.';
                //self::log $error . "\n";
                $errors[] = $error;

                continue;
            }
            $content = file_get_contents($path);
            $content = preg_replace($pattern, $replacement, $content);
            file_put_contents($path, $content);
        }

        if (count($errors)) return $errors;

        return true;
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
        // Add tag
        if(is_null($newRepositoryDir)) $newRepositoryDir = $this->getLocalNewRepositoryDir();
        $headHash = $this->getHeadHash();
        $this->exec('git --git-dir="' . $newRepositoryDir . '/.git" fetch --tags');
        $this->exec('git --git-dir="' . $newRepositoryDir . '/.git" tag -f "' . $this->getTargetDeployLastTag() . '" ' . $headHash);

        // Delete tag
        $this->exec('git --git-dir="' . $newRepositoryDir . '/.git" push --tags origin :refs/tags/' . $this->getTargetDeployLastTag());
        // Push to origin
        $this->exec('git --git-dir="' . $newRepositoryDir . '/.git" push --tags origin ' . $this->checkoutBranch);
    }

    protected function getHeadHash($repositoryDir = null)
    {
        $this->logger->debug(__METHOD__);

        // Check if repositoryDir exists
        if(!file_exists($repositoryDir . '/.git')) return 'HEAD';

        if(is_null($repositoryDir)) $repositoryDir = $this->getLocalNewRepositoryDir();
        $hash = $this->exec('git --git-dir="' . $repositoryDir . '/.git" rev-parse HEAD');

        return $hash;
    }

    /**
     * Get git from-to hash between two repositories
     * @return array($gitHashFrom, $gitHashTo)
     */
    protected function getHashFromCurrentCodeToNewRepository()
    {
        $repoDirFrom = $this->getLocalCurrentCodeDir();
        $repoDirTo = $this->getLocalNewRepositoryDir();

        return $this->getHashFromTo($repoDirFrom, $repoDirTo);
    }

    protected function getHashFromTo($repoDirFrom, $repoDirTo)
    {
        $gitHashFrom = $this->getHeadHash($repoDirFrom);
        $gitHashTo = $this->getHeadHash($repoDirTo);

        return array($gitHashFrom, $gitHashTo);
    }
}
