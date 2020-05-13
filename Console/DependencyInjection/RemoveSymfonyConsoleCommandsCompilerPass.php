<?php

namespace Morebec\OrkestraBundle\Console\DependencyInjection;

use Morebec\OrkestraBundle\Console\ApplicationConsoleCommandInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the default console commands defined by symfony.
 * This is to be used by the OrkestraConsoleKernel to create custom console utilities.
 * Class RemoveSymfonyConsoleCommandsCompilerPass
 * @package Morebec\OrkestraBundle\Console\DependencyInjection
 */
class RemoveSymfonyConsoleCommandsCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        $consoleCommands = $container->findTaggedServiceIds('console.command');
        foreach ($consoleCommands as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            /** @var class-string<object> $class */
            $class = $definition->getClass();
            if (!$class) {
                throw new RuntimeException("Class for service $serviceId not found, did you register it correctly ?");
            }

            $r = new ReflectionClass($class);
            if ($r->implementsInterface(ApplicationConsoleCommandInterface::class)) {
                continue;
            }

            $container->removeDefinition($serviceId);
        }
    }
}
