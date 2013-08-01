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

class VcsFactory {
    protected $url;
    protected $branch;
    protected $isProxy;
    protected $dryMode;

    public function __construct($url, $branch, $isProxy, $dryMode) {
        $this->url = $url;
        $this->branch = $branch;
        $this->isProxy = $isProxy;
        $this->dryMode = $dryMode;
    }

    public function create($type)
    {
        switch($type) {
            case 'git':
                $vcs = new Git($this->url, $this->branch, $this->isProxy, $this->dryMode);
                break;
            default:
                throw new \Exception('VCS type: "' . $type . '" is not defined.');
        }

        return $vcs;
    }
}