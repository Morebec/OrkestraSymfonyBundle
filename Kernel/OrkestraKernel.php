<?php

namespace Morebec\OrkestraBundle\Kernel;

use Exception;
use Morebec\DateTime\SystemClock;
use const PHP_VERSION_ID;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Class OrkestraKernel.
 * Simple Kernel implementation that provides sensible defaults to allow the concept of modules.
 * The symfony configuration of modules is done in the following way:.
 *
 * config/module/{module_name}/
 *  - services.{php,xml,yaml,yml} - DI service definitions
 *  - compiler_passes.php - PHP script returning an array of compiler passes to add to the kernel
 *  - mappings.{php,xml,yaml,yml} - Doctrine mappings
 *  - routes.{php,xml,yaml,yml} - For routing
 */
abstract class OrkestraKernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles()
    {
        $contents = require $this->getConfigurationDir().'/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        // Setup System Clock
        SystemClock::now();

        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);
        $confDir = $this->getConfigurationDir();

        // Symfony Configuration
        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');

        // Project Modules
        $loader->load($confDir.'/{modules}/*/services'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{modules}/*/'.$this->environment.'/services'.self::CONFIG_EXTS, 'glob');

        $loader->load($confDir.'/{modules}/*/mappings'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{modules}/*/'.$this->environment.'/mappings'.self::CONFIG_EXTS, 'glob');

        // Compiler passes
        $files = Finder::create()->in($this->getProjectDir().'/config/modules/*/')->name('compiler_passes.php')->files();
        foreach ($files as $file) {
            $passes = require $file;
            foreach ($passes as $pass) {
                $container->addCompilerPass($pass);
            }
        }
    }

    /**
     * @throws LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getConfigurationDir();

        $routes->import($confDir.'/{routes}/'.$this->environment.'/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');

        $files = Finder::create()->in($this->getProjectDir().'/config/modules/*/')->name('routes.yaml')->files();
        foreach ($files as $file) {
            $routes->import($file->getRealPath());
        }
    }

    /**
     * Returns the configuration directory.
     */
    protected function getConfigurationDir(): string
    {
        return $this->getProjectDir().'/config';
    }
}
