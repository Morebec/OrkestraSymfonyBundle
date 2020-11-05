<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass adding {@link DomainMessageNormalizerConfigurators} to the {@link ChainedDomainMessageNormalizerConfigurator}
 */
class AddDomainMessageRouterConfiguratorsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if(!$container->hasDefinition(DomainMessageRouterConfiguratorInterface::class)) {
            return;
        }

        $configurator = $container->getDefinition(DomainMessageRouterConfiguratorInterface::class);
        if (!is_a($configurator->getClass(), ChainedDomainMessageRouterConfigurator::class, true)) {
            return;
        }

        $configuratorIds = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!is_a($definition->getClass(), DomainMessageRouterConfiguratorInterface::class, true)) {
                continue;
            }

            if ($definition === $configurator) {
                continue;
            }

            $configuratorIds[$id] = new Reference($id);
        }
        $configurator->setArgument(1, $configuratorIds);
    }
}