<?php

namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\DomainMessageBusInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Symfony Compiler pass adding middleware to the Domain Message Bus by using
 * a specific tag on services.
 * They are applied in the order they appear in the container.
 */
class AddMessageBusMiddlewareCompilerPass implements CompilerPassInterface
{
    public const MIDDLEWARE_TAG = 'domain_message_bus.middleware';

    public function process(ContainerBuilder $container)
    {
        $taggedMiddlewareServices = $container->findTaggedServiceIds(self::MIDDLEWARE_TAG);
        $domainMessageBus = $container->getDefinition(DomainMessageBusInterface::class);

        foreach ($taggedMiddlewareServices as $key => $serviceId) {
            $domainMessageBus->addMethodCall('appendMiddleware', [
                service($serviceId)
            ]);
        }
    }
}