<?php

namespace Octava\Bundle\BranchingBundle\DependencyInjection;

use Octava\Bundle\BranchingBundle\Config\AlterIncrementConfig;
use Octava\Bundle\BranchingBundle\Config\DumpTablesConfig;
use Octava\Bundle\BranchingBundle\Config\IgnoreTablesConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OctavaBranchingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('octava_branching.switch_db', $config['switch_db']);
        $container->setParameter('octava_branching.copy_db_data', $config['copy_db_data']);

        $container->getDefinition(DumpTablesConfig::class)
            ->setArguments([$config['dump_tables']]);

        $container->getDefinition(AlterIncrementConfig::class)
            ->setArguments([$config['alter_increment_map']]);

        $container->getDefinition(IgnoreTablesConfig::class)
            ->setArguments([$config['ignore_tables']]);
    }
}
