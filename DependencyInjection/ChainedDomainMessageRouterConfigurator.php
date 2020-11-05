<?php

namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\Routing\DomainMessageRouterInterface;

/**
 * Implementation of a {@link DomainMessageRouterConfiguratorInterface}
 * that allows module to define their own configurators by delegating the configuration to them.
 */
class ChainedDomainMessageRouterConfigurator implements DomainMessageRouterConfiguratorInterface
{
    /**
     * @var iterable|DomainMessageRouterConfiguratorInterface[]
     */
    private $configurators;

    /**
     * @var DomainMessageRouterCache
     */
    private $cache;

    public function __construct(DomainMessageRouterCache $cache, iterable $configurators)
    {
        $this->configurators = $configurators;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function configure(DomainMessageRouterInterface $domainMessageRouter): void
    {
        $routes = $this->cache->loadRoutes();

        $domainMessageRouter->registerRoutes($routes);

        foreach ($this->configurators as $configurator) {
            $configurator->configure($domainMessageRouter);
        }
    }
}