<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {

        $treeBuilder = new TreeBuilder('pbergman_ntfy');
        $rootNode    = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('servers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('logger')
                            ->info('The logger to use for client')
                            ->defaultValue('monolog.logger')
                        ->end()
                        ->scalarNode('name')->end()
                            ->arrayNode('client')
                                ->info(<<<EOI
The client config, this can be a reference like:

framework:
  http_client:
    scoped_clients:
      example_ntfy.client:
        base_uri: 'https://example.com'
        auth_bearer: xxxxxx

p_bergman_ntfy:
  servers:
    example.com:
      topics: example
      client: example_ntfy.client

Or a http client config which will be used to build a scoped http client like:

p_bergman_ntfy:
  servers:
    example.com:
      topics: example
      client:
        base_uri: 'https://example.ntfy.com'
        auth_bearer: 'xxxxxxxxxxxxxxxxxxxxxx'
EOI
)
                                ->beforeNormalization()
                                    ->ifString()
                                        ->then(function($x) {
                                            return ['service' => $x];
                                        })
                                    ->end()
                                ->variablePrototype()
                                ->defaultValue([
                                    'base_uri'  => 'https://ntfy.sh',
                                    'extra'     => [
                                        'trace_content' => false
                                    ]
                                ])
                                ->end()
                            ->end()
                            ->arrayNode('topics')
                                ->requiresAtLeastOneElement()
                                ->scalarPrototype()->end()
                                ->beforeNormalization()
                                ->ifString()
                                    ->castToArray()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}