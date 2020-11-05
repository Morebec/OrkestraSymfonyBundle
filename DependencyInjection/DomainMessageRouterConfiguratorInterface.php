<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\Routing\DomainMessageRouterInterface;

/**
 * This configurator should be used by users of the framework to define their routing requirements.
 */
interface DomainMessageRouterConfiguratorInterface
{
    /**
     * Configures the routes of the {@link DomainMessageRouterInterface}.
     * @param DomainMessageRouterInterface $domainMessageRouter
     * @return void
     */
    public function configure(DomainMessageRouterInterface $domainMessageRouter): void;
}