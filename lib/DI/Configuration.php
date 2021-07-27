<?php

namespace Proklung\Notifier\DI;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Proklung\Notifier\DI
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * @var boolean $debug
     */
    private $debug;

    /**
     * Configuration constructor.
     *
     * @param boolean $debug Режим отладки.
     */
    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $tb = new TreeBuilder('notifier');
            $rootNode = $tb->getRootNode();
        } else {
            $tb = new TreeBuilder();
            $rootNode = $tb->root('notifier');
        }

         $rootNode
             ->children()
             ->arrayNode('chatter_transports')
             ->useAttributeAsKey('name')
             ->prototype('scalar')->end()
             ->end()
             ->end()
             ->fixXmlConfig('texter_transport')
             ->children()
             ->arrayNode('texter_transports')
             ->useAttributeAsKey('name')
             ->prototype('scalar')->end()
             ->end()
             ->end()
             ->children()
             ->booleanNode('notification_on_failed_messages')->defaultFalse()->end()
             ->end()
             ->children()
             ->arrayNode('channel_policy')
             ->useAttributeAsKey('name')
             ->prototype('array')
             ->beforeNormalization()->ifString()->then(function (string $v) { return [$v]; })->end()
             ->prototype('scalar')->end()
             ->end()
             ->end()
             ->end()
             ->fixXmlConfig('admin_recipient')
             ->children()
             ->arrayNode('admin_recipients')
             ->prototype('array')
             ->children()
             ->scalarNode('email')->cannotBeEmpty()->end()
             ->scalarNode('phone')->defaultValue('')->end()
             ->end()
             ->end()
             ->end()
             ->end()
             ->end()
         ;

        return $tb;
    }
}
