<?php

namespace Morebec\OrkestraBundle\Console;

use Morebec\OrkestraBundle\Console\DependencyInjection\RegisterConsoleCommandCompilerPass;
use Morebec\OrkestraBundle\Kernel\OrkestraKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class OrkestraConsoleKernel extends OrkestraKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        $container->addCompilerPass(new RegisterConsoleCommandCompilerPass());
    }
}
