<?php


namespace Morebec\OrkestraSymfonyBundle\Module;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Service Configurator tailored for {@link DomainMessageHandlerInterface}
 * that are specific to a tenant.
 */
class TenantSpecificDomainMessageHandlerConfigurator extends AutoRoutedDomainMessageHandlerConfigurator
{
    public const DOMAIN_MESSAGING_ROUTING_OVERRIDES_TAG = 'domain.messaging.routing.overrides';
    public const DOMAIN_MESSAGING_ROUTING_TENANT_TAG = 'domain.messaging.routing.tenant';
    /**
     * @var string
     */
    private $tenantId;

    /**
     * TenantSpecificDomainMessageHandlerConfigurator constructor.
     * @param ContainerConfigurator $container
     * @param ServiceConfigurator $delegate
     * @param string $tenantId
     * @param string $serviceClass
     */
    public function __construct(ContainerConfigurator $container, ServiceConfigurator $delegate, string $tenantId, string $serviceClass)
    {
        parent::__construct($container, $delegate, $serviceClass);
        $this->tenantId = $tenantId;
        $this->tenant($tenantId);
    }

    public function overrides(string $domainMessageHandlerClassName): self
    {
        $this->delegate->tag(self::DOMAIN_MESSAGING_ROUTING_OVERRIDES_TAG, ['messageHandler' => $domainMessageHandlerClassName]);
        return $this;
    }

    /**
     * Adds a tenant to this domain message handler.
     * @param string $tenantId
     * @return $this
     */
    public function tenant(string $tenantId): self
    {
        $this->delegate->tag(self::DOMAIN_MESSAGING_ROUTING_TENANT_TAG, ['id' => $tenantId]);
        return $this;
    }
}