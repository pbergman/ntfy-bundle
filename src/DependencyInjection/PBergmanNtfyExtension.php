<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle\DependencyInjection;

use PBergman\Bundle\NtfyBundle\Api\StaticTopicClient;
use PBergman\Ntfy\Api\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\AsciiSlugger;

class PBergmanNtfyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $slugger = new AsciiSlugger();
        $loader  = new XmlFileLoader($container, new FileLocator(dirname(__FILE__, 2) . '/Resources/config'));
        $loader->load('services.xml');
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach ($config['servers'] as $name => $config) {
            $name   = $slugger->slug($name, '_');
            $client =  $this->getNtfyClient($name, $config, $container);

            foreach ($config['topics'] as $topic) {
                $topicName   = $name->append('_', (string)$slugger->slug($topic, '_'));
                $topicId     = $topicName->ensureEnd('.ntfy_client');
                $topicClient = $container->register((string)$topicId, StaticTopicClient::class);
                $topicClient->setPublic(false);
                $topicClient->setArguments([$topic, $client]);
                $topicClient->addTag('ntfy_client', ['client' => (string)$name, 'topic' => $topic]);
                $container->registerAliasForArgument((string)$topicId, StaticTopicClient::class, $topicName->camel()->ensureEnd('NtfyClient')->toString());
            }
        }
    }

    private function getNtfyClient(AbstractUnicodeString $name, array $config, ContainerBuilder $container): Reference
    {
        $id     = $name->ensureEnd('.ntfy_client');
        $client = $container->register((string)$id, Client::class);
        if (null !== $config['logger']) {
            $client->addMethodCall('setLogger', [new Reference($config['logger'])]);
        }
        $client->setPublic(false);
        $client->setArguments([$this->getHttpClient($name, $config, $container)]);
        $client->addTag('ntfy_client', ['client' => (string)$name]);

        $container->registerAliasForArgument((string)$id, Client::class, $name->camel()->ensureEnd('NtfyClient')->toString());

        return new Reference((string)$id);
    }

    private function getHttpClient(AbstractUnicodeString $name, array $config, ContainerBuilder $container): Reference
    {
        if (array_key_exists('service', $config['client'])) {
            return new Reference($config['client']['service']);
        }

        $clientName = $name->snake()->ensureEnd('.http_client');
        $baseUri    = $config['client']['base_uri'] ?? '';

        unset(
            $config['client']['base_uri']
        );

        $container
            ->register((string)$clientName, ScopingHttpClient::class)
            ->setFactory([ScopingHttpClient::class, 'forBaseUri'])
            ->setArguments([new Reference('http_client'), $baseUri, $config['client']])
            ->addTag('http_client.client')
            ->setPrivate(true);

        return new Reference((string)$clientName);
    }
}