<?php

namespace JordiLlonch\Bundle\DeployBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use JordiLlonch\Bundle\DeployBundle\DependencyInjection\Compiler\DeployersCompilerPass;

class DeployersCompilerPassTest extends \PHPUnit_Framework_TestCase
{

    protected $compiler;

    public function setUp()
    {
        $this->compiler = new DeployersCompilerPass();
    }

    public function testProcessTaggedPlugins()
    {
        $r = new \ReflectionObject($this->compiler);
        $m = $r->getMethod('processTaggedDeployers');
        $m->setAccessible(true);
        $m->invoke($this->compiler, $this->getFakeContainer());

        $m = $r->getMethod('getDeployer');
        $m->setAccessible(true);
        $result = $m->invoke($this->compiler, 'test');

        $this->assertEquals($result, 'deployer.test');
    }

    protected function getFakeContainer()
    {
        $channels = array(
            'deployer.test' => array(array('deployer'=> 'test'))
        );
        $container =  \Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder'
        );
        $container->shouldReceive('findTaggedServiceIds')->andReturn($channels);

        return $container;
    }
}
