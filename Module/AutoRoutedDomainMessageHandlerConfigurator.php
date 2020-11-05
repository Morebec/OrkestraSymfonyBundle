<?php


namespace Morebec\OrkestraSymfonyBundle\Module;


use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class AutoRoutedDomainMessageHandlerConfigurator
{
    public const DOMAIN_MESSAGING_ROUTING_AUTOROUTE_TAG = 'domain.messaging.routing.autoroute';
    public const DOMAIN_MESSAGING_ROUTING_DISABLED_METHOD_TAG = 'domain.messaging.routing.disabled_method';

    /**
     * @var ServiceConfigurator
     */
    protected $delegate;

    /**
     * @var ContainerConfigurator
     */
    protected $container;

    /**
     * @var string
     */
    protected $serviceClass;

    public function __construct(ContainerConfigurator $container, ServiceConfigurator $delegate, string $serviceClass)
    {
        // These parameters are fake we will just work with the delegate.
        $this->delegate = $delegate;
        $this->container = $container;
        $this->serviceClass = $serviceClass;
    }

    /**
     * Disables a certain domain messaging method route.
     * @return $this
     */
    public function disableMethodRoute(string $methodName): self
    {
        $this->delegate->tag(self::DOMAIN_MESSAGING_ROUTING_DISABLED_METHOD_TAG, ['name' => $methodName]);
        return $this;
    }

    /**
     * Creates a configuration that is specific to a tenant.
     * @param string $tenantId
     * @return TenantSpecificDomainMessageHandlerConfigurator
     */
    public function tenant(string $tenantId): TenantSpecificDomainMessageHandlerConfigurator
    {
        return new TenantSpecificDomainMessageHandlerConfigurator(
            $this->container, $this->delegate, $tenantId, $this->serviceClass
        );
    }
}