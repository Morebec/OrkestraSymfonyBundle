<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;


use Morebec\Orkestra\Messaging\Normalization\DomainMessageNormalizerInterface;

/**
 * Implementation of a {@link DomainMessageNormalizerInterface}
 * that allows module to define their own configurators by delegating the configuration to them.
 */
class ChainedDomainMessageNormalizerConfigurator implements DomainMessageNormalizerConfiguratorInterface
{
    /**
     * @var iterable|DomainMessageNormalizerConfiguratorInterface[]
     */
    private $configurators;

    public function __construct(iterable $configurators)
    {
        $this->configurators = $configurators;
    }

    /**
     * @inheritDoc
     */
    public function configure(DomainMessageNormalizerInterface $domainMessageNormalizer): void
    {
        foreach ($this->configurators as $configurator) {
            $configurator->configure($domainMessageNormalizer);
        }
    }
}