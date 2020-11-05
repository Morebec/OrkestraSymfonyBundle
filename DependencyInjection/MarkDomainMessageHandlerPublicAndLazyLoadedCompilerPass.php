<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\DomainMessageHandlerInterface;
use Morebec\Orkestra\Messaging\Routing\ContainerDomainMessageHandlerProvider;
use Morebec\Orkestra\Messaging\Routing\DomainMessageHandlerProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass marks domain message handlers as public and lazy loaded
 * if the {@link DomainMessageHandlerProviderInterface} is implemented by the {@link ContainerDomainMessageHandlerProvider}.
 */
class MarkDomainMessageHandlerPublicAndLazyLoadedCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(DomainMessageHandlerProviderInterface::class)) {
            return;
        }

        $domainMessageHandler = $container->getDefinition(DomainMessageHandlerProviderInterface::class);
        if (!$domainMessageHandler->getClass() === ContainerDomainMessageHandlerProvider::class) {
            return;
        }

        foreach ($container->getDefinitions() as $definition) {
            if (!is_a($definition->getClass(), DomainMessageHandlerInterface::class, true)) {
                continue;
            }
            $definition->setPublic(true);
            $definition->setLazy(true);
        }
    }
}