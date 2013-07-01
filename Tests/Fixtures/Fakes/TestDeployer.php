<?php
/**
 * @author Jordi Llonch <llonch.jordi@gmail.com>
 * @date 01/07/13 20:31
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