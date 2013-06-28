<?php

namespace JordiLlonch\Bundle\DeployBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use JordiLlonch\Bundle\DeployBundle\Library\DeployerExecute;

abstract class BaseCommand extends ContainerAwareCommand
{
    protected $deployer;

    protected function configure()
    {
        $this->addOption('zones', null, InputOption::VALUE_OPTIONAL, 'Zones to execute command. It must exists in jordi_llonch_deploy.zones config.');

        // TODO: dry mode
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Init deployer engine
        $this->deployer = $this->getContainer()->get('jordi_llonch_deploy.engine');
        $this->deployer->setOutput($output);
        $this->deployer->setLogger($this->getContainer()->get('logger'));
        // TODO: dry mode
        //$this->deployer->setDryMode(...);

        // Selected zones
        $optionZones = $input->getOption('zones');
        if(!empty($optionZones)) $this->deployer->setSelectedZones(explode(",", $optionZones));
    }
}