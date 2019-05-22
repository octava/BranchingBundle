<?php


namespace Octava\Bundle\BranchingBundle\DependencyInjection\Compiler;


use Octava\Bundle\BranchingBundle\ConnectionFactory;
use Octava\Bundle\BranchingBundle\Manager\DatabaseManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UpdateDoctrineConnectionFactoryPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $container
            ->getDefinition('doctrine.dbal.connection_factory')
            ->setClass(ConnectionFactory::class)
            ->addMethodCall('setDatabaseManager', [new Reference(DatabaseManager::class)]);
    }
}
