<?php

namespace Proklung\Notifier\DI;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Mailer\Mailer;

/**
 * Class ConfigurationMailer
 * @package Proklung\Notifier\DI
 */
final class ConfigurationMailer implements ConfigurationInterface
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
            $tb = new TreeBuilder('mailer');
            $rootNode = $tb->getRootNode();
        } else {
            $tb = new TreeBuilder();
            $rootNode = $tb->root('mailer');
        }

         $rootNode
             ->{!class_exists(FullStack::class) && class_exists(Mailer::class) ? 'canBeDisabled' : 'canBeEnabled'}()
             ->validate()
             ->ifTrue(function ($v) { return isset($v['dsn']) && \count($v['transports']); })
             ->thenInvalid('"dsn" and "transports" cannot be used together.')
             ->end()
             ->fixXmlConfig('transport')
             ->children()
             ->scalarNode('dsn')->defaultNull()->end()
             ->scalarNode('dsn_file')->defaultNull()->end()
             ->scalarNode('default_email_from')->defaultNull()->end()
             ->scalarNode('default_email_title')->defaultNull()->end()
             ->arrayNode('transports')
             ->useAttributeAsKey('name')
             ->prototype('scalar')->end()
             ->end()
             ->arrayNode('envelope')
             ->info('Mailer Envelope configuration')
             ->children()
             ->scalarNode('sender')->end()
             ->arrayNode('recipients')
             ->performNoDeepMerging()
             ->beforeNormalization()
             ->ifArray()
             ->then(function ($v) {
                 return array_filter(array_values($v));
             })
             ->end()
             ->prototype('scalar')->end()
             ->end()
             ->end()
             ->end()
             ->end()
             ->end()
         ;

        return $tb;
    }
}
