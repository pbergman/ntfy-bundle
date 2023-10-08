<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle\DependencyInjection\CompilerPass;

use PBergman\Bundle\NtfyBundle\Command\PublishCommand;
use PBergman\Bundle\NtfyBundle\Command\SubscribeCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CommandTagPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $subscribe = $container->getDefinition(SubscribeCommand::class);
        $publish   = $container->getDefinition(PublishCommand::class);
        $clients   = [];

        foreach ($container->findTaggedServiceIds('ntfy_client') as $id => $tag) {

            if (isset($tag[0]['topic'])) {
                $clients[$tag[0]['client']]['topics'][$tag[0]['topic']] = new Reference($id);
                continue;
            }

            $clients[$tag[0]['client']]['client'] = new Reference($id);
        }

        $subscribe->setArgument(0, $clients);
        $publish->setArgument(0, $clients);
    }
}