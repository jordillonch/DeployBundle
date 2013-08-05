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

class CleanCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deployer:clean')
            ->setDescription('Remove old code. Left <info>clean_max_deploys</info> deploys.')
            ->setHelp(<<<EOT
The <info>deployer:clean</info> removes old code. Left <info>clean_max_deploys</info> deploys..
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $this->deployer->runClean();
    }
}