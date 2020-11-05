<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass adding {@link DomainMessageNormalizerConfigurators} to the {@link ChainedDomainMessageNormalizerConfigurator}
 */
class AddDomainMessageNormalizersConfiguratorsCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if(!$container->hasDefinition(DomainMessageNormalizerConfiguratorInterface::class)) {
            return;
        }

        $configurator = $container->getDefinition(DomainMessageNormalizerConfiguratorInterface::class);
        if (!is_a($configurator->getClass(), ChainedDomainMessageNormalizerConfigurator::class, true)) {
            return;
        }

        $configuratorIds = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!is_a($definition->getClass(), DomainMessageNormalizerConfiguratorInterface::class, true)) {
                continue;
            }

            if ($definition === $configurator) {
                continue;
            }

            $configuratorIds[$id] = new Reference($id);
        }
        $configurator->setArguments([$configuratorIds]);
    }
}