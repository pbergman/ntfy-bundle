<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PBergmanNtfyBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DependencyInjection\CompilerPass\CommandTagPass());
    }
}