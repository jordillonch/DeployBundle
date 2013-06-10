<?php

namespace JordiLlonch\Bundle\DeployBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use JordiLlonch\Bundle\DeployBundle\DependencyInjection\Compiler\DeployersCompilerPass;

class JordiLlonchDeployBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DeployersCompilerPass());
    }
}
