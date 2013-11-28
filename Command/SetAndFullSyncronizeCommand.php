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

class SetAndFullSyncronizeCommand extends BaseCommand
{
    protected $configure;

    protected function configure()
    {
        $this->addArgument('zone', InputArgument::REQUIRED, 'Zone name. It must exists in jordi_llonch_deploy.zones config.');
        $this->addArgument('urls', InputArgument::OPTIONAL, 'Url/s separated by comma');

        $this
            ->setName('deployer:set-and-full-syncronize')
            ->setDescription('Set urls to a zone and do initialize, downloand and code2production operation. If exception occurs urls are restored.')
            ->setHelp(<<<EOT
The <info>deployer:set-and-full-syncronize</info> .
EOT
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->configure = $this->getContainer()->get('jordillonch_deployer.configure');
        $this->deployer = $this->getContainer()->get('jordillonch_deployer.engine');
        $this->deployer->setOutput($output);
        $this->setLogger($input);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        list($parametersFile, $zone, $sanitazedUrls, $urls) = $this->getInputParameters($input);
        $this->setNewUrlsZonesConfigTemporary($zone, $sanitazedUrls);
        $this->deployer->adquireZonesLockOrThrowException();
        $this->deploy($zone);
        $this->persistNewUrlsConfig($zone, $urls, $parametersFile);
        $this->deployer->releaseZonesLock();
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

    /**
     * @param $response
     * @param $zone
     */
    protected function handleResponse($response, $zone)
    {
        if ($response[$zone] === false) {
            throw new \Exception('Error on zone ' . $zone, 1);
        }
    }

    /**
     * @param $zone
     * @param $urls
     * @param $parametersFile
     */
    protected function persistNewUrlsConfig($zone, $urls, $parametersFile)
    {
        $this->configure->readParametersFile($parametersFile);
        $this->configure->set($zone, $urls);
        $this->configure->writeParametersFile();
        $this->cacheClear();
    }

    /**
     * @param $zone
     */
    protected function deploy($zone)
    {
        $response = $this->deployer->initialize();
        $this->handleResponse($response, $zone);
        $response = $this->deployer->syncronize();
        $this->handleResponse($response, $zone);
        $response = $this->deployer->runCode2Production();
        $this->handleResponse($response, $zone);
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getInputParameters(InputInterface $input)
    {
        $rootDir = $this->getContainer()->getParameter('kernel.root_dir') . '/../';
        $deployConfig = $this->getContainer()->getParameter('jordi_llonch_deploy.config');
        $parametersFile = $rootDir . DIRECTORY_SEPARATOR . $deployConfig['servers_parameter_file'];
        $zone = $input->getArgument('zone');
        $urls = $input->getArgument('urls');
        $sanitazedUrls = $this->configure->sanitizeUrl($urls);

        return array($parametersFile, $zone, $sanitazedUrls, $urls);
    }

    /**
     * @param $zone
     * @param $sanitazedUrls
     */
    protected function setNewUrlsZonesConfigTemporary($zone, $sanitazedUrls)
    {
        $this->deployer->setSelectedZones(explode(',', $zone));
        $this->deployer->setUrls($sanitazedUrls);
    }
}