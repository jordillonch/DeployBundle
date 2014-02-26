<?php
/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\VCS;


use Psr\Log\LoggerInterface;

class Git implements VcsInterface {
    protected $url;
    protected $branch;
    protected $isProxy;
    protected $destinationPath;
    protected $dryMode;
    protected $logger;

    public function __construct($url, $branch, $isProxy, $dryMode)
    {
        $this->url = $url;
        $this->branch = $branch;
        $this->isProxy = $isProxy;
        $this->dryMode = $dryMode;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed $destinationPath
     */
    public function setDestinationPath($destinationPath)
    {
        $this->destinationPath = $destinationPath;
    }

    protected function exec($command, &$output = null)
    {
        $this->logger->debug('exec: ' . $command);

        if ($this->dryMode) return;

        $outputLastLine = exec($command, $output, $returnVar);
        if ($returnVar != 0) throw new \Exception('ERROR executing: ' . $command . "\n" . implode("\n", $output));

        if(!empty($output)) foreach($output as $item) $this->logger->debug('exec output: ' . $item);

        return $outputLastLine;
    }

    public function cloneCodeRepository()
    {
        // Update repo if it is a proxy of a remote repo
        if ($this->isProxy) {
            $urlParsed = parse_url($this->url);
            $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" --work-tree="' . $urlParsed['path'] . '" reset --hard HEAD');
            $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" --work-tree="' . $urlParsed['path'] . '" checkout "' . $this->branch . '"');
            $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" --work-tree="' . $urlParsed['path'] . '" pull');
        }

        // Clone repo
        $this->exec('git clone "' . $this->url . '" "' . $this->destinationPath . '" --branch "' . $this->branch . '" --depth=1');

        // Overwrite origin remote if it is a proxy
        if ($this->isProxy) {
            $originUrlProxyRepo = $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" config --get remote.origin.url');
            $this->exec('git --git-dir="' . $this->destinationPath . '/.git" config --replace-all remote.origin.url "' . $originUrlProxyRepo . '"');
        }
    }

    public function getLastVersionFromRemote()
    {
        // Get last version from remote
        if($this->isProxy)
        {
            $urlParsed = parse_url($this->url);
            $vcsVersions = $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" ls-remote origin ' . $this->branch);
        }
        else $vcsVersions = $this->exec('git ls-remote "' . $this->url . '" origin ' . $this->branch);

        $vcsVersions = explode("\n", $vcsVersions);
        if (!isset($vcsVersions[0])) throw new \Exception("Git repository empty.");
        $vcsVersion = '';
        foreach ($vcsVersions as $item) if (\preg_match('/' . $this->branch . '/', $item)) $vcsVersion = $item;
        if (empty($vcsVersion)) throw new \Exception("Git branch " . $this->branch . " not found.");
        $vcsVersion = explode("\t", $vcsVersion);
        $vcsVersion = $vcsVersion[0];
        if (empty($vcsVersion)) throw new \Exception("Unable to get last git version.");

        return $vcsVersion;
    }

    public function getHeadHash($pathVcs = null)
    {
        // Check if repositoryDir exists
        if(!file_exists($pathVcs . '/.git')) return 'HEAD';

        if(is_null($pathVcs)) $pathVcs = $this->destinationPath;
        $hash = $this->exec('git --git-dir="' . $pathVcs . '/.git" rev-parse HEAD');

        return $hash;
    }

    public function pushLastDeployTag($tag, $pathVcs = null)
    {
        // Add tag
        if(is_null($pathVcs)) $pathVcs = $this->destinationPath;
        $headHash = $this->getHeadHash();
        $this->exec('git --git-dir="' . $pathVcs . '/.git" fetch --tags');
        $this->exec('git --git-dir="' . $pathVcs . '/.git" tag -f "' . $tag . '" ' . $headHash);

        // Delete tag
        $this->exec('git --git-dir="' . $pathVcs . '/.git" push --tags origin :refs/tags/' . $tag);
        // Push to origin
        $this->exec('git --git-dir="' . $pathVcs . '/.git" push --tags origin ' . $this->branch);
    }

    public function getDiffFiles($dirFrom, $dirTo)
    {
        if (!$this->isProxy) throw new \Exception(__METHOD__ . ' method only works if zone uses a repository proxy.');

        $gitUidFrom = $this->getHeadHash($dirFrom);
        $gitUidTo = $this->getHeadHash($dirTo);
        if ($gitUidFrom && $gitUidFrom) {
            $urlParsed = parse_url($this->url);
            $this->exec('git --git-dir="' . $urlParsed['path'] . '/.git" diff ' . $gitUidTo . ' ' . $gitUidFrom . ' --name-only', $diffFiles);

            return $diffFiles;
        }

        return array();
    }
}