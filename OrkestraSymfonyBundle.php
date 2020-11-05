<?php

namespace Morebec\OrkestraSymfonyBundle;

use Morebec\OrkestraSymfonyBundle\DependencyInjection\AddDomainMessageNormalizersConfiguratorsCompilerPass;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\AddDomainMessageRouterConfiguratorsCompilerPass;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\ChainedDomainMessageRouterConfigurator;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\DomainMessageRouterCache;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\MarkDomainMessageHandlerPublicAndLazyLoadedCompilerPass;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\RegisterRoutesForDomainMessageHandlersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OrkestraSymfonyBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MarkDomainMessageHandlerPublicAndLazyLoadedCompilerPass());
        $container->addCompilerPass(new AddDomainMessageRouterConfiguratorsCompilerPass());
        $container->addCompilerPass(new AddDomainMessageNormalizersConfiguratorsCompilerPass());
        $routerCache = new DomainMessageRouterCache($container->getParameter('kernel.cache_dir'));
        $container->addCompilerPass(new RegisterRoutesForDomainMessageHandlersCompilerPass(
            $routerCache
        ));
    }

    public function boot()
    {
    }

    public function shutdown()
    {
    }
}