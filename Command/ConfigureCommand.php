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
use JordiLlonch\Bundle\DeployBundle\Service\Configure;

class ConfigureCommand extends BaseCommand
{
    protected function configure()
    {
        $this->addArgument('zone', InputArgument::REQUIRED, 'Zone name. It must exists in jordi_llonch_deploy.zones config.');
        $this->addArgument('action', InputArgument::REQUIRED, 'Action: add, set, rm, list, list_json');
        $this->addArgument('url', InputArgument::OPTIONAL, 'Url/s separated by comma');

        $this
            ->setName('deployer:configure')
            ->setDescription('Configure urls Zones.')
            ->setHelp(<<<EOT
The <info>deployer:configure_zone</info> .
EOT
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $configure = $this->getContainer()->get('jordillonch_deployer.configure');
        $rootDir = $this->getContainer()->getParameter('kernel.root_dir') . '/../';
        $deployConfig = $this->getContainer()->getParameter('jordi_llonch_deploy.config');
        $parametersFile = $rootDir . DIRECTORY_SEPARATOR . $deployConfig['servers_parameter_file'];
        $configure->readParametersFile($parametersFile);

        // Validations
        $zone = $input->getArgument('zone');
        $action = $input->getArgument('action');
        $url = $input->getArgument('url');

        switch($action)
        {
            case 'add' :
                $configure->add($zone, $url);
                break;
            case 'set' :
                $configure->set($zone, $url);
                break;
            case 'rm' :
                $configure->rm($zone, $url);
                break;
            case 'list' :
                $output->writeln($configure->listUrls($zone));
                break;
            case 'list_json' :
                $output->writeln($configure->listUrls($zone, Configure::OUTPUT_JSON));
                break;
            default :
                throw new \Exception('Actions must be: add, set, rm, list or list_json');
        }

        $configure->writeParametersFile();
        $this->cacheClear();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }

    protected function cacheClear()
    {
        $realCacheDir = $this->getContainer()->getParameter('kernel.cache_dir');
        $oldCacheDir = $realCacheDir . '_old';
        $filesystem = $this->getContainer()->get('filesystem');
        $this->getContainer()->get('cache_clearer')->clear($realCacheDir);
        $filesystem->rename($realCacheDir, $oldCacheDir);
        $filesystem->remove($oldCacheDir);
    }
}