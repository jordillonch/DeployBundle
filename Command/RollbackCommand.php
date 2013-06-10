<?php

namespace JordiLlonch\Bundle\DeployBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class RollbackCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deployer:rollback')
            ->setDescription('Rollback code that it is on production environment with a previous version on configured servers.')
            ->setHelp(<<<EOT
The <info>deployer:rollback</info> command rollback code that it is on production environment with a previous version on configured servers.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $confirmation = $this->getHelper('dialog')->askConfirmation($output, '<question>WARNING! You are about to execute a rollback. Are you sure you wish to continue? (y/n)</question>', 'y');
        if ($confirmation === true) {
            $this->deployer->runRollback();
        } else {
            $output->writeln('<error>Rollback cancelled!</error>');
        }
    }
}