<?php

namespace Octava\Bundle\BranchingBundle;

use Octava\Bundle\BranchingBundle\DependencyInjection\Compiler\GenerateDatabasesPass;
use Octava\Bundle\BranchingBundle\DependencyInjection\Compiler\UpdateDoctrineConnectionFactoryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OctavaBranchingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new UpdateDoctrineConnectionFactoryPass());
    }
}
