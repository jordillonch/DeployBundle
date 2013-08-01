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

interface VcsInterface {
    public function __construct(
        $url,
        $branch,
        $isProxy,
        $dryMode
    );

    public function setLogger(LoggerInterface $logger);
    public function setDestinationPath($destinationPath);
    public function cloneCodeRepository();
    public function getDiffFiles($dirFrom, $dirTo);
    public function getLastVersionFromRemote();
    public function pushLastDeployTag($pathVcs = null);
    public function getHeadHash($pathVcs = null);
}