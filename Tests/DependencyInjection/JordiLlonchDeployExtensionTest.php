<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Tests\DependencyInjection;

use JordiLlonch\Bundle\DeployBundle\DependencyInjection\JordiLlonchDeployExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class JordiLlonchDeployExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $container;
    protected $extension;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.root_dir', '/tmp/app');
        $config = Yaml::parse($this->getBundleConfig());
        $this->extension = new JordiLlonchDeployExtension();
        $this->extension->load(array($config), $this->container);
    }

    public function testContainerDefinitionAndLoadExtension()
    {
        $this->assertTrue($this->container->has('jordillonch_deployer.configure'));
    }

    protected function getBundleConfig()
    {
        $zonesServers = <<<'EOF'
test:
    urls:
        - jllonch@testserver1
EOF;
        mkdir('/tmp/app', 0777);
        file_put_contents('/tmp/app/parameters_deployer_servers.yml', $zonesServers);

        return <<<'EOF'
config:
    project: MyProject
    vcs: git
    servers_parameter_file: app/parameters_deployer_servers.yml
    local_repository_dir: /tmp/deployer_local_repository
    clean_max_deploys: 10
    ssh:
        user: myuser
        public_key_file: '~/.ssh/id_rsa.pub'
        private_key_file: '~/.ssh/id_rsa'
zones:
    test:
        deployer: test
        environment: prod
        urls:
            - jllonch@testserver1
        checkout_url: 'git@github.com:jordillonch/JordiLlonchDeployBundle.git'
        checkout_branch: master
        repository_dir: /var/www/production/test/deploy
        production_dir: /var/www/production/test/code
EOF;
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->container = null;
        $this->extension = null;
        exec('rm -rf /tmp/app');
    }
}
