<?php
namespace Octava\Bundle\BranchingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('octava_branching');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode
            ->children()
                ->arrayNode('switch_db')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Enable/disable auto switch db name by git branch name')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('connections')
                            ->info('List of connections, connections names must be similar with dbal connections')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('url')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('ignore_tables')
                            ->info('List of tables witch will be copy without data')
                            ->defaultValue([])
                            ->prototype('scalar')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $rootNode
            ->children()
                ->arrayNode('alter_increment_map')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('test')
                                ->children()
                                    ->integerNode('start')->isRequired()->end()
                                    ->integerNode('step')->isRequired()->end()
                                ->end()
                            ->end()
                            ->arrayNode('dev')
                                ->children()
                                    ->integerNode('start')->isRequired()->end()
                                    ->integerNode('step')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
