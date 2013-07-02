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