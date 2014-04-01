<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Command;

use Akamon\Bundle\DeployBundle\Service\Engine;
use JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

abstract class BaseCommand extends ContainerAwareCommand
{
    /** @var Engine */
    protected $deployer;

    protected function configure()
    {
        $this->addOption('zones', null, InputOption::VALUE_REQUIRED, 'Zones to execute command. It must exists in jordi_llonch_deploy.zones config.');

        // TODO: dry mode
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->deployer = $this->getContainer()->get('jordillonch_deployer.engine');
        $this->deployer->setSelectedZones(explode(",", $input->getOption('zones')));
        $this->deployer->setOutput($output);
        $this->setLogger($input);
        // TODO: dry mode
        //$this->deployer->setDryMode(...);
        $this->deployer->adquireZonesLockOrThrowException();
    }

    /**
     * @param InputInterface $input
     */
    protected function setLogger(InputInterface $input)
    {
        // Logger
        $logger = $this->getContainer()->get('logger');
        if ($input->getOption('verbose')) {
            $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        }
        $this->deployer->setLogger($logger);
    }
}