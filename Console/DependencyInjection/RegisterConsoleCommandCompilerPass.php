<?php

namespace Morebec\OrkestraBundle\Console\DependencyInjection;

use Morebec\OrkestraBundle\Console\ApplicationConsoleCommandInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterConsoleCommandCompilerPass implements CompilerPassInterface
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
