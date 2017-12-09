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
                ->append($this->addSwitchDbNode())
                ->append($this->addAlterIncrementNode())
            ->end();

        return $treeBuilder;
    }

    public function addSwitchDbNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('switch_db');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')
                    ->info('Enable/disable auto switch db name by git branch name')
                    ->defaultFalse()
                ->end()
                ->arrayNode('connection_urls')
                    ->info('List of connection urls')
                    ->defaultValue([])
                    ->performNoDeepMerging()
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('ignore_tables')
                    ->info('List of tables which will be copy without data')
                    ->performNoDeepMerging()
                    ->defaultValue([])
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
            ->end();

        return $node;
    }

    public function addAlterIncrementNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('alter_increment_map');

        $node
            ->performNoDeepMerging()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->arrayPrototype()
                    ->children()
                        ->arrayNode('test')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('start')
                                    ->defaultValue(50000000)
                                    ->isRequired()
                                ->end()
                                ->integerNode('step')
                                    ->defaultValue(1000)
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('dev')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('start')
                                    ->defaultValue(5000000)
                                    ->isRequired()
                                ->end()
                                ->integerNode('step')
                                    ->defaultValue(1000)
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
