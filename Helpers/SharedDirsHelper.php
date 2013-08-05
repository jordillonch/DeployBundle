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

class SharedDirsHelper extends Helper {
    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'shared_dirs';
    }

    /**
     * Initialize given path in the shared directory
     * @param string $path
     */
    public function initialize($path)
    {
        $sharedDir = $this->getDeployer()->getRemoteSharedDir();
        $sudo = '';
        if($this->getDeployer()->getSudo()) $sudo = 'sudo ';
        $this->getDeployer()->execRemoteServers($sudo . 'mkdir -p ' . $sharedDir . '/' . $path);
        $this->getDeployer()->execRemoteServers($sudo . 'chmod a+wrx ' . $sharedDir . '/' . $path);
    }

    /**
     * Set shared directory for a given path in the project that will be removed and replaced by a symlink to
     * given path to shared directory
     * @param string $pathInAppToLink
     * @param string $pathInSharedDir
     */
    public function set($pathInPrjToLink, $pathInSharedDir)
    {
        $this->getDeployer()->exec('rm -rf ' . $this->getDeployer()->getLocalNewRepositoryDir() . '/' . $pathInPrjToLink);
        $this->getDeployer()->exec('ln -s ' . $this->getDeployer()->getRemoteSharedDir() . '/' . $pathInSharedDir . ' ' . $this->getDeployer()->getLocalNewRepositoryDir() . '/' . $pathInPrjToLink);
    }
}