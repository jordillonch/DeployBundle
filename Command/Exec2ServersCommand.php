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

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class Exec2ServersCommand extends BaseCommand
{
    protected function configure()
    {
       parent::configure();

       $this
            ->setName('deployer:exec2servers')
            ->setDescription('Executes command passed as argument to all configured servers.')
            ->addOption('command', null, InputOption::VALUE_REQUIRED, 'Command to execute.')
//            ->addArgument('command', InputArgument::REQUIRED, 'Command to execute.', null)
            ->setHelp(<<<EOT
The <info>deployer:exec2servers</info> command Executes command passed as argument to all configured servers.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $command = $input->getOption('command');
      if(empty($command))
      {
          $output->writeln('<error>You must provide a command argument.</error>');
      }
      else
      {
          $this->deployer->execAll($command);
      }
    }
}