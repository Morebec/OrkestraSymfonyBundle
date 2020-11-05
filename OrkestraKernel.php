<?php

namespace Morebec\OrkestraSymfonyBundle;

use Morebec\OrkestraSymfonyBundle\Module\SymfonyOrkestraModuleConfiguratorInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Orkestra's implementation of the symfony kernel to allow a custom
 * Module and configuration system.
 */
class OrkestraKernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @var SymfonyOrkestraModuleConfiguratorInterface[]
     */
    private $moduleConfigurators;
    
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        $this->moduleConfigurators = [];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $projectDir = $this->getProjectDir();
        $container->import($projectDir.'/config/{packages}/*.yaml');
        $container->import($projectDir.'/config/{packages}/'.$this->environment.'/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/services.yaml')) {
            $container->import($projectDir.'/config/services.yaml');
            $container->import($projectDir.'/config/{services}_'.$this->environment.'.yaml');
        } elseif (is_file($path = \dirname(__DIR__).'/config/services.php')) {
            (require $path)($container->withPath($path), $this);
        }

        $this->configureModuleContainer($container);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $projectDir = $this->getProjectDir();
        $routes->import($projectDir.'/config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import($projectDir.'/config/{routes}/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/routes.yaml')) {
            $routes->import($projectDir.'/config/routes.yaml');
        } elseif (is_file($path = \dirname(__DIR__).'/config/routes.php')) {
            (require $path)($routes->withPath($path), $this);
        }

        $this->configureModuleRoutes($routes);
    }

    /**
     * Configures the Orkestra Modules.
     * @param ContainerConfigurator $container
     */
    protected function configureModuleContainer(ContainerConfigurator $container): void
    {
        $contents = require $this->getProjectDir().'/config/modules.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                $configuratorClassName = "{$class}";
                if (!class_exists($configuratorClassName)) {
                    throw new \RuntimeException("Configurator '$class' could not be loaded. Did you correctly registered it with the autoloader?");
                }

                /** @var SymfonyOrkestraModuleConfiguratorInterface $configurator */
                $configurator = new $configuratorClassName();
                $this->moduleConfigurators[] = $configurator;

                $configurator->configureContainer($container);


            }
        }
    }

    private function configureModuleRoutes(RoutingConfigurator $routes)
    {
        foreach ($this->moduleConfigurators as $moduleConfigurator) {
            $moduleConfigurator->configureRoutes($routes);
        }
    }
}