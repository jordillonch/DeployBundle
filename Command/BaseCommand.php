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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

abstract class BaseCommand extends ContainerAwareCommand
{
    protected $deployer;

    protected function configure()
    {
        $this->addOption('zones', null, InputOption::VALUE_REQUIRED, 'Zones to execute command. It must exists in jordi_llonch_deploy.zones config.');

        // TODO: dry mode
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Init deployer engine
        $this->deployer = $this->getContainer()->get('jordillonch_deployer.engine');
        $this->deployer->setOutput($output);

        // Logger
        $logger = $this->getContainer()->get('logger');
        if($input->getOption('verbose')) {
            $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        }
        $this->deployer->setLogger($logger);

        // TODO: dry mode
        //$this->deployer->setDryMode(...);

        // Selected zones
        $optionZones = $input->getOption('zones');
        $this->deployer->setSelectedZones(explode(",", $optionZones));
    }
}