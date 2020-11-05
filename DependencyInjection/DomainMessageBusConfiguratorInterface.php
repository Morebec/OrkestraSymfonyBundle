<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\DomainMessageBusInterface;

/**
 * This configurator should be used by users of the framework to define their middleware requirements.
 */
interface DomainMessageBusConfiguratorInterface
{
    /**
     * Configures the {@link DomainMessageBusInterface}.
     * @param DomainMessageBusInterface $domainMessageBus
     */
    public function configure(DomainMessageBusInterface $domainMessageBus): void;
}