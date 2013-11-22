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


use JordiLlonch\Bundle\DeployBundle\Service\Engine;
use Psr\Log\NullLogger;

class EngineTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    public function testExecuteAnyMethodOk()
    {
        $zoneManager = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\ZoneManager');
        $zoneManager->shouldReceive('getZonesNames')->andReturn(array('zone1', 'zone2'));
        $fakeZone = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer');
        $fakeZone->shouldReceive('anyMethodToTest')->twice()->andReturn('foo');
        $zoneManager->shouldReceive('getZone')->with('zone1')->andReturn($fakeZone);
        $zoneManager->shouldReceive('getZone')->with('zone2')->andReturn($fakeZone);
        $engine = $this->getEngine($zoneManager);

        $r = $engine->anyMethodToTest();
        $this->assertSame('foo', $r['zone1']);
        $this->assertSame('foo', $r['zone2']);
    }

    public function testExecuteRunDownloadCodeOk()
    {
        $zoneManager = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\ZoneManager');
        $zoneManager->shouldReceive('getZonesNames')->andReturn(array('zone1', 'zone2'));
        $fakeZone = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer');
        $fakeZone->shouldReceive('runDownloadCode')->twice()->andReturn('foo');
        $fakeZone->shouldReceive('runDownloadCodeRollback')->never();
        $fakeZone->shouldReceive('setNewVersionRollback')->never();
        $zoneManager->shouldReceive('getZone')->with('zone1')->andReturn($fakeZone);
        $zoneManager->shouldReceive('getZone')->with('zone2')->andReturn($fakeZone);
        $engine = $this->getEngine($zoneManager);

        $r = $engine->runDownloadCode();
        $this->assertSame('foo', $r['zone1']);
        $this->assertSame('foo', $r['zone2']);
    }

    public function testExecuteRunDownloadCodeError()
    {
        $zoneManager = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\ZoneManager');
        $zoneManager->shouldReceive('getZonesNames')->andReturn(array('zone1', 'zone2'));
        $fakeZone = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer');
        $fakeZone->shouldReceive('runDownloadCode')->once()->andThrow('Exception');
        $fakeZone->shouldReceive('runDownloadCodeRollback')->twice();
        $fakeZone->shouldReceive('setNewVersionRollback')->twice();
        $zoneManager->shouldReceive('getZone')->andReturn($fakeZone);
        $engine = $this->getEngine($zoneManager);

        $r = $engine->runDownloadCode();
        $this->assertFalse($r['zone1']);
        $this->assertFalse($r['zone2']);
    }

    public function testExecuteRunCode2ProductionOk()
    {
        $zoneManager = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\ZoneManager');
        $zoneManager->shouldReceive('getZonesNames')->andReturn(array('zone1', 'zone2'));
        $fakeZone = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer');
        $fakeZone->shouldReceive('runCode2Production')->twice()->andReturn('foo');
        $fakeZone->shouldReceive('runCode2ProductionRollback')->never();
        $fakeZone->shouldReceive('setNewVersionRollback')->never();
        $zoneManager->shouldReceive('getZone')->with('zone1')->andReturn($fakeZone);
        $zoneManager->shouldReceive('getZone')->with('zone2')->andReturn($fakeZone);
        $engine = $this->getEngine($zoneManager);

        $r = $engine->runCode2Production();
        $this->assertSame('foo', $r['zone1']);
        $this->assertSame('foo', $r['zone2']);
    }

    public function testExecuteRunCode2ProductionError()
    {
        $zoneManager = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\ZoneManager');
        $zoneManager->shouldReceive('getZonesNames')->andReturn(array('zone1', 'zone2'));
        $fakeZone = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer');
        $fakeZone->shouldReceive('runCode2Production')->once()->andThrow('Exception');
        $fakeZone->shouldReceive('runCode2ProductionRollback')->twice();
        $zoneManager->shouldReceive('getZone')->andReturn($fakeZone);
        $engine = $this->getEngine($zoneManager);

        $r = $engine->runCode2Production();
        $this->assertFalse($r['zone1']);
        $this->assertFalse($r['zone2']);
    }

    protected function tearDown()
    {
        \Mockery::close();

        parent::tearDown();
    }

    /**
     * @param $zoneManager
     * @return Engine
     */
    protected function getEngine($zoneManager)
    {
        $locker = \Mockery::mock('JordiLlonch\Bundle\DeployBundle\Service\LockInterface');
//        $locker->shouldReceive('acquire')->andReturn(true);
//        $locker->shouldReceive('release')->andReturn(true);
        $locker->shouldReceive('releaseAll')->andReturn(true);
        $engine = new Engine($zoneManager, $locker);
        $engine->setLogger(new NullLogger());
        $output = \Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $output->shouldReceive('writeln');
        $engine->setOutput($output);

        return $engine;
    }


}
 