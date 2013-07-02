<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Tests\Fixtures\Fakes;

use JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer;

class TestDeployer extends BaseDeployer {
    public function downloadCode()
    {
    }

    public function downloadCodeRollback()
    {
    }

    protected function runClearCache()
    {
    }

}