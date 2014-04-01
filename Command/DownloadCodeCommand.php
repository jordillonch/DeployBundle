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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class DownloadCodeCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deployer:download')
            ->addOption('branch', null, InputArgument::OPTIONAL, 'Choose a different branch than configured one.')
            ->setDescription('Download code to configured servers.')
            ->setHelp(<<<EOT
The <info>deployer:download</info> command download code to all configured servers.
Downloaded code far than 7 days it will be removed.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branch = $input->getOption('branch');
        $this->deployer->runDownloadCode($branch);
    }
}