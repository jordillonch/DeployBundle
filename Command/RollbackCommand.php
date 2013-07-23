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

class RollbackCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this->addArgument('action', InputArgument::REQUIRED, 'Action: list, execute');
        $this->addArgument('version', InputArgument::OPTIONAL, 'Version to rollback');

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
        $action = $input->getArgument('action');
        switch($action)
        {
            case 'list' :
                $this->deployer->setSilent(true);
                $list = $this->deployer->getRollbackList();
                foreach ($list as $zoneName => $zone) {
                    $versions = array();
                    foreach ($zone as $id => $item) $versions[] = array($id, $item['date']->format('Y/m/d H:i:s'), $item['hash_small']);

                    $table = $this->getHelperSet()->get('table');
                    $table->setHeaders(array('Version (use this on deployer:rollback execute)', 'Date', 'Short hash'));
                    $table->setRows($versions);
                    if(count($versions))
                    {
                        $output->writeln('<info>[' . $zoneName . ']</info>');
                        $table->render($output);
                    }
                }
                break;
            case 'execute' :
                $version = $input->getArgument('version');
                if(empty($version)) throw new \Exception('"version" parameter is required for execute action.');

                if($input->isInteractive()) {
                    $dialog = $this->getHelperSet()->get('dialog');
                    $confirmation = $dialog->askConfirmation($output, '<question>WARNING! You are about to execute a rollback. Are you sure you wish to continue? (y/n)</question>', false);
                }
                else $confirmation = true;

                if ($confirmation === true) {
                    $this->deployer->runRollback($version);
                } else {
                    $output->writeln('<error>Rollback cancelled!</error>');
                }
                break;
            default :
                throw new \Exception('Actions must be: list or execute');
        }
    }
}