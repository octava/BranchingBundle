<?php

namespace Octava\Bundle\BranchingBundle\DependencyInjection;

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

        $container->getDefinition('octava_branching.config.dump_tables_config')
            ->addArgument($config['dump_tables']);

        $container->getDefinition('octava.branching.config.alter_increment_config')
            ->addArgument($config['alter_increment_map']);
    }
}
