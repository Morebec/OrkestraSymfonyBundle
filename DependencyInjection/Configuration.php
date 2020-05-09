<?php

namespace Morebec\OrkestraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('orkestra');
        $root = $tree->getRootNode();

        $root
            ->children()
                ->scalarNode('command_bus')
                    ->defaultValue('command.bus')
                ->end()
                ->scalarNode('event_bus')
                    ->defaultValue('event.bus')
                ->end()
                ->scalarNode('query_bus')
                    ->defaultValue('query.bus')
                ->end()
                ->scalarNode('notification_bus')
                    ->defaultValue('notification.bus')
                ->end()
            ->end()
        ;

        return $tree;
    }
}
