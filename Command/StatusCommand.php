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

class StatusCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this->addOption('send_warning_n_days_deploy', null, InputOption::VALUE_OPTIONAL, 'Send email warning if N days from last deploy.');

        $this
            ->setName('deployer:status')
            ->setDescription('Get running and downloaded versions for every location.')
            ->setHelp(<<<EOT
The <info>deployer:status</info> gets running and downloaded versions for every location.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $option_send_warning_n_days_deploy = $input->getOption('send_warning_n_days_deploy');
        if(empty($option_send_warning_n_days_deploy))
        {
            $status = $this->deployer->getStatus();
            foreach($status as $location => $info)
            {
                $output->writeln('<info>[' . $location . ']</info>');
                $output->writeln('Running version:         ' . $info['current_version']);
                $output->writeln('Last downloaded version: ' . $info['new_version']);
            }
        }
        else
        {
            $this->deployer->sendWarningNDaysDeploy($option_send_warning_n_days_deploy);
        }
    }
}