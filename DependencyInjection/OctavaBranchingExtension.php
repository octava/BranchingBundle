<?php

namespace Octava\Bundle\BranchingBundle\DependencyInjection;

use Octava\Bundle\BranchingBundle\Config\AlterIncrementConfig;
use Octava\Bundle\BranchingBundle\Config\SwitchConfig;
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

        $config['switch_db']['project_dir'] = $container->getParameter('kernel.project_dir');

        $container->getDefinition(SwitchConfig::class)->setArguments([$config['switch_db']]);
        $container->getDefinition(AlterIncrementConfig::class)->setArguments([$config['alter_increment_map']]);
    }
}
