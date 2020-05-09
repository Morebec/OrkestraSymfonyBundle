<?php

namespace Morebec\OrkestraBundle\DependencyInjection;

use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OrkestraExtension extends Extension implements PrependExtensionInterface
{
    /** @var array loaded configuration */
    private $config = [];

    public function getConfig(): array
    {
        try {
            return $this->config;
        } finally {
            // Erase the config after it is retrieved for security
            $this->config = [];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['MonologBundle'])) {
            // Add messenger audit channel
            /* @var MonologBundle $monologBundle */
            // $monologBundle = $bundles['MonologBundle'];
            $container->prependExtensionConfig('monolog_bundle', ['channels' => ['messenger_audit']]);
        }
    }
}
