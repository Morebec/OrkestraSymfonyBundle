<?php


namespace Morebec\OrkestraSymfonyBundle\Module;

use Morebec\Orkestra\Messaging\DomainMessageHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Service Configurator tailored for {@link DomainMessageHandlerInterface}.
 */
class DomainMessageHandlerConfigurator
{
    public const DOMAIN_MESSAGING_HANDLER_TAG = 'orkestra.domain.messaging.handler';

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
        $this->delegate->tag(self::DOMAIN_MESSAGING_HANDLER_TAG);
    }

    /**
     * Allows to automatically register this domain message handler with the router.
     * @return AutoRoutedDomainMessageHandlerConfigurator
     */
    public function autoroute(): AutoRoutedDomainMessageHandlerConfigurator
    {
        $this->delegate->tag(AutoRoutedDomainMessageHandlerConfigurator::DOMAIN_MESSAGING_ROUTING_AUTOROUTE_TAG);
        return new AutoRoutedDomainMessageHandlerConfigurator($this->container, $this->delegate, $this->serviceClass);
    }

    /**
     * Creates a configuration that is specific to a tenant.
     * @param string $tenantId
     * @return TenantSpecificDomainMessageHandlerConfigurator
     */
    public function tenant(string $tenantId): TenantSpecificDomainMessageHandlerConfigurator
    {
        return new TenantSpecificDomainMessageHandlerConfigurator(
            $this->container,
            $this->delegate,
            $tenantId,
            $this->serviceClass
        );
    }

    public function public(): self
    {
        $this->delegate->public();
        return $this;
    }

    public function autowire(): self
    {
        $this->delegate->autowire();
        return $this;
    }

    public function autoconfigure(): self
    {
        $this->delegate->autoconfigure();
        return $this;
    }

    public function tag(string $name, array $attributes = []): self
    {
        $this->delegate->tag($name, $attributes);
        return $this;
    }

    public function decorate(?string $id, string $renamedId = null, int $priority = 0, int $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): self
    {
        $this->delegate->decorate($id, $renamedId, $priority, $invalidBehavior);
        return $this;
    }

    public function deprecate(): self
    {
        $this->delegate->deprecate();
        return $this;
    }

    public function args(array $arguments): self
    {
        $this->delegate->args($arguments);
        return $this;
    }

    public function alias(string $id, string $referenceId): self
    {
        $this->delegate->alias($id, $referenceId);
        return $this;
    }

    public function lazy(bool $lazy = true): self
    {
        $this->delegate->lazy($lazy);
        return $this;
    }

    public function share(bool $shared = true): self
    {
        $this->delegate->share($shared);
        return $this;
    }
}
