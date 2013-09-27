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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class SyncronizeCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();
      
        $this
            ->setName('deployer:syncronize')
            ->setDescription('Syncronize servers with all deployed versions of code.')
            ->setHelp(<<<EOT
The <info>deployer:sync</info> command ensure that all downloaded versions of code are copied to all servers.
Useful when you add a new server to a zone. If you not syncronize the new server the rollback operation will break the code in the new server.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $this->deployer->syncronize();
    }
}