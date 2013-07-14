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

use Symfony\Component\Finder\Finder;

trait Symfony2 {
    /**
     * Do a cache:warmup for production environment
     */
    protected function helperSymfony2CacheWarmUp()
    {
        // INFO: cache is worn up in the deploy server but paths are wrong, so after warning up paths are corrected
        $localNewRepositoryDir = $this->getLocalNewRepositoryDir();
        $this->exec('rm -rf ' . $localNewRepositoryDir . '/app/cache');
        $this->exec('php ' . $localNewRepositoryDir . '/app/console cache:warmup --env=prod --no-debug');

        // Replace absolute paths for absolute paths in the production environment
        $finder = new Finder();
        $finder->in($localNewRepositoryDir . '/app/cache');
        $finder->files();
        $paths = array();
        foreach ($finder as $file) $paths[] = $file->getRealPath();
        $pattern = '/' . str_replace('/', '\\/', $localNewRepositoryDir) . '/';
        $replace = $this->getRemoteNewRepositoryDir();
        $this->filesReplacePattern($paths, $pattern, $replace);
        $this->exec('chmod -R a+wr ' . $localNewRepositoryDir . '/app/cache');
    }
}