<?php

namespace Octava\Bundle\BranchingBundle\DependencyInjection\Compiler;

use Octava\Bundle\BranchingBundle\Config\IgnoreTablesConfig;
use Octava\Bundle\BranchingBundle\Helper\Database;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwitchDbNameCompiler implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $originalDbName = $container->getParameter('database_name');
        $container->setParameter('database_name_original', $originalDbName);

        if ($container->hasParameter('database_driver')) {
            $databaseDriver = $container->getParameter('database_driver');
        } else {
            $definition = $container->getDefinition('doctrine.dbal.default_connection');
            $databaseDriver = $definition->getArgument(0)['driver'];
            $container->setParameter('database_driver', $databaseDriver);
        }

        if (!$container->getParameter('octava_branching.switch_db')
            || false === strpos($databaseDriver, 'mysql')
            || false === in_array($container->getParameter('kernel.environment'), ['dev', 'test'])
        ) {
            return;
        }

        $helper = $container->get(Database::class);
        $branchDbName = $helper->generateDatabaseName();

        if ($originalDbName != $branchDbName) {
            if (!$helper->databaseExists($branchDbName)) {
                $helper->generateDatabase(
                    $branchDbName,
                    $container->get(IgnoreTablesConfig::class)->getIgnoreTables()
                );
            }
            $container->setParameter('database_name', $branchDbName);

            $definition = $container->getDefinition('doctrine.dbal.default_connection');
            $connectionParams = $definition->getArgument(0);
            $connectionParams['dbname'] = $branchDbName;
            $definition->replaceArgument(0, $connectionParams);
        }
    }
}
