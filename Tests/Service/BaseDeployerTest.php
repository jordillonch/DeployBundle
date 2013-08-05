<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Tests\Service;

use JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer;

class BaseDeployerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FakeDeployer
     */
    protected $fakeDeployer;
    
    public function setUp()
    {
        $this->fakeDeployer = new FakeDeployer();
        $this->fakeDeployer->setZoneName('test_zone');
        $generalConfig = array(
            'project' => 'myproj',
            'environment' => 'prod',
            'local_repository_dir' => '/tmp',
            'vcs' => 'git',
            'checkout_url' => 'http://git',
            'checkout_branch' => 'master',
            'repository_dir' => '/var/www/repo1',
            'production_dir' => '/var/www/code1',
            'ssh' => array(
                'user' => 'myuser',
                'public_key_file' => '~/.ssh/id_rsa.pub',
                'private_key_file' => '~/.ssh/id_rsa'
            ),
            'helper' => array(
                'test' => array(
                    'foo' => 'bar'
                )
            ),
            'custom' => array(
                'test_c' => array(
                    'abc' => '123',
                    'def' => '456',
                )
            ),
        );
        $zonesConfig = array(
            'test_zone' => array(
                'urls' => array('server1', 'server2'),
                'local_repository_dir' => '/tmp',
                'repository_dir' => '/var/www/repo2',
                'production_dir' => '/var/www/code2',
                'helper' => array(
                    'test' => array(
                        'foo' => 'bar2'
                    )
                ),
                'custom' => array(
                    'test_c' => array(
                        'def' => '789'
                    )
                ),
            )
        );
        $this->fakeDeployer->setConfig($generalConfig, $zonesConfig);
    }

    public function testConfig()
    {
        $this->assertSame('test_zone', $this->fakeDeployer->getZoneName());
        $this->assertSame('prod', $this->fakeDeployer->getEnvironment());
        $this->assertSame('/tmp', $this->fakeDeployer->getLocalRepositoryDir());
        $this->assertSame('/var/www/code2', $this->fakeDeployer->getRemoteProductionCodeDir());
        $this->assertSame('/var/www/repo2/' . $this->fakeDeployer->getZoneName(), $this->fakeDeployer->getRemoteRepositoryDir());
        $this->assertSame('bar2', $this->fakeDeployer->getHelpersConfig()['test']['foo']);
        $this->assertSame('123', $this->fakeDeployer->getCustom()['test_c']['abc']);
        $this->assertSame('789', $this->fakeDeployer->getCustom()['test_c']['def']);
        $this->assertNull($this->fakeDeployer->getStatus()['current_version']);
        $this->assertNull($this->fakeDeployer->getStatus()['new_version']);
        $this->assertInstanceOf('\JordiLlonch\Bundle\DeployBundle\SSH\SshManager', $this->fakeDeployer->getSshManager());
        $this->assertInstanceOf('\JordiLlonch\Bundle\DeployBundle\VCS\VcsInterface', $this->fakeDeployer->getVcs());
        $this->assertInstanceOf('\JordiLlonch\Bundle\DeployBundle\Helpers\HelperSet', $this->fakeDeployer->getHelperSet());
    }

    public function testInitialize()
    {

    }

    protected function tearDown()
    {
        parent::tearDown();
    }

}

class FakeDeployer extends BaseDeployer
{
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