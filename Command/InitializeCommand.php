<?php

namespace JordiLlonch\Bundle\DeployBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class InitializeCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();
      
        $this
            ->setName('deployer:initialize')
            ->setDescription('Initialize directories on configured servers.')
            ->setHelp(<<<EOT
The <info>deployer:initialize</info> command initialize directories on all configured servers.
It must be executed one time before deploy cycles.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $this->deployer->initialize();
    }
}