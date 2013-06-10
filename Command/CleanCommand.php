<?php

namespace JordiLlonch\Bundle\DeployBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class CleanCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deployer:clean')
            ->setDescription('Download code to configured servers.')
            ->setHelp(<<<EOT
The <info>deployer:download</info> command download code to all configured servers.
Downloaded code far than 7 days it will be removed.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $this->deployer->runClean();
    }
}