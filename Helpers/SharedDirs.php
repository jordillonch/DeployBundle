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

trait SharedDirs {
    /**
     * Initialize given path in the shared directory
     * @param string $path
     */
    protected function helperSharedDirInitialize($path)
    {
        $sharedDir = $this->getRemoteSharedDir();
        $this->mkdirRecursive($sharedDir . '/' . $path, 0777);
        $sudo = '';
        if($this->sudo) $sudo = 'sudo ';
        $this->execRemoteServers($sudo . 'mkdir -p ' . $sharedDir . '/' . $path);
        $this->execRemoteServers($sudo . 'chmod a+wrx ' . $sharedDir . '/' . $path);
    }

    /**
     * Set shared directory for a given path in the project that will be removed and replaced by a symlink to
     * given path to shared directory
     * @param string $pathInAppToLink
     * @param string $pathInSharedDir
     */
    protected function helperSharedDirSet($pathInPrjToLink, $pathInSharedDir)
    {
        $this->exec('rm -rf ' . $this->getLocalNewRepositoryDir() . '/' . $pathInPrjToLink);
        $this->exec('ln -s ' . $this->getRemoteSharedDir() . '/' . $pathInSharedDir . ' ' . $this->getLocalNewRepositoryDir() . '/' . $pathInPrjToLink);
    }
}