<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\Routing\DomainMessageHandlerRouteBuilder;
use Morebec\Orkestra\Messaging\Routing\DomainMessageRouteCollection;
use Morebec\Orkestra\Messaging\Routing\DomainMessageRouterInterface;
use Morebec\Orkestra\Messaging\Routing\Tenant\TenantSpecificMessageHandlerRouteBuilder;
use Morebec\OrkestraSymfonyBundle\Module\AutoRoutedDomainMessageHandlerConfigurator;
use Morebec\OrkestraSymfonyBundle\Module\DomainMessageHandlerConfigurator;
use Morebec\OrkestraSymfonyBundle\Module\SymfonyOrkestraModuleContainerConfigurator;
use Morebec\OrkestraSymfonyBundle\Module\TenantSpecificDomainMessageHandlerConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This compiler pass checks for domain messaging routing information in service tags definition
 * to determine how to configure the router for the different domain.message.handlers.
 */
class RegisterRoutesForDomainMessageHandlersCompilerPass implements CompilerPassInterface
{
    /**
     * @var DomainMessageRouterCache
     */
    private $routerCache;

    public function __construct(DomainMessageRouterCache $routerCache)
    {
        $this->routerCache = $routerCache;
    }

    public function process(ContainerBuilder $container)
    {
        $messageRouter = $container->getDefinition(DomainMessageRouterInterface::class);
        if (!$messageRouter) {
            return;
        }

        $routes = new DomainMessageRouteCollection();

        $messageHandlerIds = $container->findTaggedServiceIds(DomainMessageHandlerConfigurator::DOMAIN_MESSAGING_HANDLER_TAG);

        foreach ($messageHandlerIds as $serviceId => $_) {
            $definition = $container->getDefinition($serviceId);
            $tags = $definition->getTags();

            $autoroute = false;
            $disabledMethods = [];
            $tenantIds = [];
            $tenantOverrides = [];

            foreach ($tags as $tag => $attributes) {
                switch ($tag) {
                    case AutoRoutedDomainMessageHandlerConfigurator::DOMAIN_MESSAGING_ROUTING_AUTOROUTE_TAG:
                        $autoroute = true;
                        break;

                    case AutoRoutedDomainMessageHandlerConfigurator::DOMAIN_MESSAGING_ROUTING_DISABLED_METHOD_TAG:
                        foreach ($attributes as $attribute) {
                            $methodName = $attribute['name'];
                            $disabledMethods[$methodName] = $methodName;
                        }
                        break;

                    case TenantSpecificDomainMessageHandlerConfigurator::DOMAIN_MESSAGING_ROUTING_TENANT_TAG:
                        foreach ($attributes as $attribute) {
                            $tenantId = $attribute['id'];
                            $tenantIds[$tenantId] = $tenantId;
                        }
                        break;

                    case TenantSpecificDomainMessageHandlerConfigurator::DOMAIN_MESSAGING_ROUTING_OVERRIDES_TAG:
                        foreach ($attributes as $attribute) {
                            $handlerName = $attribute['messageHandler'];
                            $tenantOverrides[$handlerName] = $handlerName;
                        }
                        break;
                }
            }

            if (!$autoroute) {
                continue;
            }
            if (empty($tenantIds)) {
                $builder = DomainMessageHandlerRouteBuilder::forDomainMessageHandler($serviceId);
                foreach ($disabledMethods as $disabledMethod) {
                    $builder->withMethodDisabled($disabledMethod);
                }

                $routes->addAll($builder->build());
            } else {
                foreach ($tenantIds as $tenantId) {
                    $builder = TenantSpecificMessageHandlerRouteBuilder::forDomainMessageHandler($tenantId, $serviceId);
                    foreach ($disabledMethods as $disabledMethod) {
                        $builder->withMethodDisabled($disabledMethod);
                    }
                    foreach ($tenantOverrides as $override) {
                        $builder->overridesMessageHandler($override);
                    }
                    $routes->addAll($builder->build());
                }
            }
        }

        // Symfony does not allow injecting objects as method calls in the container.
        // To overcome this, we have to dump the routes in a file in cache along with the container
        // and provide it a way to load these routes.
        $this->routerCache->dumpRoutes($routes);
    }
}
