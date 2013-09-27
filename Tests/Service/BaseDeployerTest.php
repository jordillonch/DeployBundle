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

use JordiLlonch\Bundle\DeployBundle\Helpers\HelperSet;
use JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer;
use Psr\Log\NullLogger;

class BaseDeployerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FakeDeployer
     */
    protected $fakeDeployer;
    
    public function setUp()
    {
        $this->fakeDeployer = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Tests\Service\FakeDeployer')->makePartial();

        $this->fakeDeployer->setHelperSet(new HelperSet(array()));
        $this->fakeDeployer->setZoneName('test_zone');
        $generalConfig = array(
            'project' => 'myproj',
            'environment' => 'prod',
            'local_repository_dir' => '/tmp/test_deploy_bundle/local_repo',
            'vcs' => 'fake',
            'checkout_url' => 'http://git',
            'checkout_branch' => 'master',
            'repository_dir' => '/tmp/test_deploy_bundle/remote/var/www/repo1',
            'production_dir' => '/tmp/test_deploy_bundle/remote/var/www/code1',
            'clean_max_deploys' => 2,
            'ssh' => array(
                'proxy' => 'local',
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
                'local_repository_dir' => '/tmp/test_deploy_bundle/local_repo',
                'repository_dir' => '/tmp/test_deploy_bundle/remote/var/www/repo2',
                'production_dir' => '/tmp/test_deploy_bundle/remote/var/www/code2',
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
        $this->fakeDeployer->setLogger(new NullLogger());
    }

    public function testConfig()
    {
        $this->fakeDeployer->initialize();
        $this->fakeDeployer->setCurrentVersion('20130101_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->setNewVersion('20130102_000000_LAST_VERSION_HASH');
        $this->assertSame('test_zone', $this->fakeDeployer->getZoneName());
        $this->assertSame('prod', $this->fakeDeployer->getEnvironment());
        $this->assertSame('/tmp/test_deploy_bundle/local_repo', $this->fakeDeployer->getLocalRepositoryDir());
        $this->assertSame('/tmp/test_deploy_bundle/local_repo/test_zone/code', $this->fakeDeployer->getLocalCodeDir());
        $this->assertSame('/tmp/test_deploy_bundle/local_repo/test_zone/data', $this->fakeDeployer->getLocalDataDir());
        $this->assertSame('/tmp/test_deploy_bundle/local_repo/test_zone/data/current_version', $this->fakeDeployer->getLocalDataCurrentVersionFile());
        $this->assertSame('/tmp/test_deploy_bundle/local_repo/test_zone/data/new_version', $this->fakeDeployer->getLocalDataNewVersionFile());
        $this->assertSame('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getLocalNewRepositoryDir());
        $this->assertSame('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getLocalCurrentCodeDir());
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/shared_code', $this->fakeDeployer->getRemoteSharedDir());
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code', $this->fakeDeployer->getRemoteCodeDir());
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getRemoteCurrentRepositoryDir());
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getRemoteNewRepositoryDir());
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/code2', $this->fakeDeployer->getRemoteProductionCodeDir());
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/' . $this->fakeDeployer->getZoneName(), $this->fakeDeployer->getRemoteRepositoryDir());
        $this->assertSame('bar2', $this->fakeDeployer->getHelpersConfig()['test']['foo']);
        $this->assertSame('123', $this->fakeDeployer->getCustom()['test_c']['abc']);
        $this->assertSame('789', $this->fakeDeployer->getCustom()['test_c']['def']);
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
        $this->assertSame('prod', $this->fakeDeployer->getEnvironment());
        $this->assertFalse($this->fakeDeployer->getSudo());
        $this->assertInstanceOf('\JordiLlonch\Bundle\DeployBundle\SSH\SshManager', $this->fakeDeployer->getSshManager());
        $this->assertInstanceOf('\JordiLlonch\Bundle\DeployBundle\VCS\VcsInterface', $this->fakeDeployer->getVcs());
        $this->assertInstanceOf('\JordiLlonch\Bundle\DeployBundle\Helpers\HelperSet', $this->fakeDeployer->getHelperSet());
    }

    public function testInitialize()
    {
        // Initialize
        $this->fakeDeployer->initialize();
        $this->assertFileExists('/tmp/test_deploy_bundle/local_repo/test_zone/code');
        $this->assertFileExists('/tmp/test_deploy_bundle/local_repo/test_zone/data');
        $this->assertFileExists('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/bin');
        $this->assertFileExists('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code');
        $this->assertFileExists('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/shared_code');
        $this->assertNull($this->fakeDeployer->getStatus()['current_version']);
        $this->assertNull($this->fakeDeployer->getStatus()['new_version']);
    }

    public function testDownload()
    {
        $this->fakeDeployer->shouldReceive('downloadCode')->once();
        $this->fakeDeployer->shouldReceive('setNewVersion')->once()->passthru();

        $this->fakeDeployer->initialize();
        $newVersion = '20130101_000000';
        $this->fakeDeployer->runDownloadCode($newVersion);
        $this->assertNull($this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage It seems deployer has not been initialized.
     */
    public function testDownloadWithoutInitializing()
    {
        $this->fakeDeployer->runDownloadCode('');
    }

    public function testCode2Production()
    {
        $this->fakeDeployer->shouldReceive('code2ProductionBefore')->once();
        $this->fakeDeployer->shouldReceive('code2ProductionAfter')->once();

        $this->fakeDeployer->initialize();
        $this->fakeDeployer->setNewVersion('20130101_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->runCode2Production('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130101_000000_LAST_VERSION_HASH');
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130101_000000_LAST_VERSION_HASH', readlink('/tmp/test_deploy_bundle/remote/var/www/code2'));
    }

    public function testRollbackByVersion()
    {
        $this->fakeDeployer->shouldReceive('runCode2Production')->once()->passthru();

        $this->fakeDeployer->initialize();
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130101_000000_LAST_VERSION_HASH', 0777, true);
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130102_000000_LAST_VERSION_HASH', 0777, true);
        $this->fakeDeployer->setCurrentVersion('20130102_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->setNewVersion('20130102_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->runRollback('20130101_000000_LAST_VERSION_HASH');
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130101_000000_LAST_VERSION_HASH', readlink('/tmp/test_deploy_bundle/remote/var/www/code2'));
    }

    public function testRollbackByNumeric()
    {
        $this->fakeDeployer->shouldReceive('runCode2Production')->once()->passthru();

        $this->fakeDeployer->initialize();
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130101_000000_LAST_VERSION_HASH', 0777, true);
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130102_000000_LAST_VERSION_HASH', 0777, true);
        $this->fakeDeployer->setCurrentVersion('20130102_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->setNewVersion('20130102_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->runRollback('1');
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130101_000000_LAST_VERSION_HASH', readlink('/tmp/test_deploy_bundle/remote/var/www/code2'));
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Current version is not found in the available versions list.
     */
    public function testRollbackExceptionCurrentVersionNotFound()
    {
        $this->fakeDeployer->initialize();
        $this->fakeDeployer->runRollback('1');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage There are only 2 available versions to step backward.
     */
    public function testRollbackExceptionAvailableVersions()
    {
        $this->fakeDeployer->initialize();
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130101_000000_LAST_VERSION_HASH', 0777, true);
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130102_000000_LAST_VERSION_HASH', 0777, true);
        $this->fakeDeployer->setCurrentVersion('20130102_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->setNewVersion('20130102_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->runRollback('2');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage 20130101_000000_LAST_VERSION_HASH version not found.
     */
    public function testRollbackExceptionVersionNotFound()
    {
        $this->fakeDeployer->initialize();
        $this->fakeDeployer->runRollback('20130101_000000_LAST_VERSION_HASH');
    }

    public function testCompleteDeployProcessAndRollback()
    {
        // Initialize
        $this->fakeDeployer->initialize();

        // Download
        $newVersion = '20130101_000000';
        $this->fakeDeployer->runDownloadCode($newVersion);
        $this->assertNull($this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);

        // Code2Production
        $this->fakeDeployer->runCode2Production();
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130101_000000_LAST_VERSION_HASH', readlink('/tmp/test_deploy_bundle/remote/var/www/code2'));

        // Download
        $newVersion2 = '20130102_000000';
        $this->fakeDeployer->runDownloadCode($newVersion2);
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);

        // Code2Production
        $this->fakeDeployer->runCode2Production();
        $this->assertSame('20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130102_000000_LAST_VERSION_HASH', readlink('/tmp/test_deploy_bundle/remote/var/www/code2'));

        // Rollback
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130101_000000_LAST_VERSION_HASH', 0777, true);
        mkdir('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130102_000000_LAST_VERSION_HASH', 0777, true);
        $this->fakeDeployer->runRollback('1');
        $this->assertSame('20130101_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['current_version']);
        $this->assertSame('20130102_000000_LAST_VERSION_HASH', $this->fakeDeployer->getStatus()['new_version']);
        $this->assertSame('/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code/20130101_000000_LAST_VERSION_HASH', readlink('/tmp/test_deploy_bundle/remote/var/www/code2'));
    }

    public function testSetZoneName()
    {
        $this->fakeDeployer->setZoneName('zone_a');
        $this->assertSame('zone_a', $this->fakeDeployer->getZoneName());
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Bad zone name. Only lower case characters and numbers are accepted.
     */
    public function testSetZoneNameBadZoneNameException()
    {
        $this->fakeDeployer->setZoneName('INVALID');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Bad zone name. Only lower case characters and numbers are accepted.
     */
    public function testSetZoneNameBadZoneNameException2()
    {
        $this->fakeDeployer->setZoneName('invalid name');
    }

    public function testRsync2Servers()
    {
        $this->fakeDeployer->shouldReceive('rsync')->with('orig_path', 'server1', 'target_path', '')->once();
        $this->fakeDeployer->shouldReceive('rsync')->with('orig_path', 'server2', 'target_path', '')->once();
        $this->fakeDeployer->rsync2Servers('orig_path', 'target_path');
    }

    public function testCode2Servers()
    {
        $this->fakeDeployer->initialize();
        $this->fakeDeployer->setNewVersion('20130102_000000_LAST_VERSION_HASH');
        $this->fakeDeployer->shouldReceive('syncronize')->andReturn(true)->once();
        $this->fakeDeployer->shouldReceive('rsync2Servers')->with('/tmp/test_deploy_bundle/local_repo/test_zone/code/20130102_000000_LAST_VERSION_HASH', '/tmp/test_deploy_bundle/remote/var/www/repo2/test_zone/code', '');

        $this->fakeDeployer->code2Servers();
    }

    public function testClean()
    {
        $this->fakeDeployer->initialize();
        mkdir($this->fakeDeployer->getLocalCodeDir() . '/20130101_000000_LAST_VERSION_HASH');
        mkdir($this->fakeDeployer->getLocalCodeDir() . '/20130102_000000_LAST_VERSION_HASH');
        mkdir($this->fakeDeployer->getLocalCodeDir() . '/20130103_000000_LAST_VERSION_HASH');
        mkdir($this->fakeDeployer->getLocalCodeDir() . '/20130104_000000_LAST_VERSION_HASH');
        mkdir($this->fakeDeployer->getLocalCodeDir() . '/20130105_000000_LAST_VERSION_HASH');

        $this->fakeDeployer->shouldReceive('exec')->times(3)->passthru();
        $this->fakeDeployer->shouldReceive('execRemoteServers')->times(3);

        $this->fakeDeployer->clean();

        $this->assertFileNotExists($this->fakeDeployer->getLocalCodeDir() . '/20130101_000000_LAST_VERSION_HASH');
        $this->assertFileNotExists($this->fakeDeployer->getLocalCodeDir() . '/20130102_000000_LAST_VERSION_HASH');
        $this->assertFileNotExists($this->fakeDeployer->getLocalCodeDir() . '/20130103_000000_LAST_VERSION_HASH');
        $this->assertFileExists($this->fakeDeployer->getLocalCodeDir() . '/20130104_000000_LAST_VERSION_HASH');
        $this->assertFileExists($this->fakeDeployer->getLocalCodeDir() . '/20130105_000000_LAST_VERSION_HASH');
    }


    protected function tearDown()
    {
        exec('rm -rf /tmp/test_deploy_bundle');
        \Mockery::close();
        $this->fakeDeployer = null;

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