<?php

namespace Octava\Bundle\BranchingBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Octava\Bundle\BranchingBundle\DependencyInjection\Compiler\SwitchDbNameCompiler;

class OctavaBranchingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SwitchDbNameCompiler());
    }
}
