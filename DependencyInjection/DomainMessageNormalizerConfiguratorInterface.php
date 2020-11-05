<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\Normalization\DomainMessageNormalizerInterface;

/**
 * Service responsible for the configuration of the {@link DomainMessageNormalizerInterface}.
 */
interface DomainMessageNormalizerConfiguratorInterface
{
    /**
     * Configures the {@link DomainMessageNormalizerInterface}
     * @param DomainMessageNormalizerInterface $domainMessageNormalizer
     */
    public function configure(DomainMessageNormalizerInterface $domainMessageNormalizer): void;
}