<?php

namespace Morebec\OrkestraSymfonyBundle\Module;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ParametersConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;

/**
 * Helper class containing methods to easily configure services that are orkestra flavored.
 */
class SymfonyOrkestraModuleContainerConfigurator
{
    /** @var string */
    public const UPCASTER_TAG = 'orkestra.upcaster';

    /** @var string */
    public const PROJECTOR_TAG = 'orkestra.projector';

    /** @var string */
    public const DOMAIN_MESSAGE_VALIDATOR = 'orkestra.domain_message_validator';

    /** @var string */
    public const DOMAIN_MESSAGE_AUTHORIZER = 'orkestra.domain_message_authorizer';

    /**
     * @var ContainerConfigurator
     */
    private $container;

    /**
     * @var ServicesConfigurator
     */
    private $services;

    public function __construct(ContainerConfigurator $container)
    {
        $this->container = $container;
        $this->services = $container->services();
    }

    /**
     * Adds a command handler service definition.
     * Configured as autowired, autoconfigured, public and lazy.
     * @param string $serviceId
     * @param string|null $className
     * @return DomainMessageHandlerConfigurator
     */
    public function commandHandler(string $serviceId, string $className = null): DomainMessageHandlerConfigurator
    {
        return $this->messageHandler($serviceId, $className);
    }

    /**
     * Adds an event handler definition.
     * Configured as autowired, autoconfigured, public and lazy.
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return DomainMessageHandlerConfigurator
     */
    public function eventHandler(string $serviceId, ?string $serviceClass = null): DomainMessageHandlerConfigurator
    {
        return $this->messageHandler($serviceId, $serviceClass);
    }

    /**
     * Adds a Process Manager service definition.
     * COnfigured as autowired, autoconfigured, public and lazy
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return DomainMessageHandlerConfigurator
     */
    public function processManager(string $serviceId, ?string $serviceClass = null): DomainMessageHandlerConfigurator
    {
        return $this->messageHandler($serviceId, $serviceClass);
    }

    /**
     * Adds a repository service definition.
     * Configured as autowired, autoconfigured, public and lazy.
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return ServiceConfigurator
     */
    public function repository(string $serviceId, ?string $serviceClass = null): ServiceConfigurator
    {
        return $this->service($serviceId, $serviceClass);
    }

    /**
     * Adds an query handler definition.
     * Configured as autowired, autoconfigured, public and lazy.
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return DomainMessageHandlerConfigurator
     */
    public function queryHandler(string $serviceId, ?string $serviceClass = null): DomainMessageHandlerConfigurator
    {
        return $this->messageHandler($serviceId, $serviceClass);
    }

    /**
     * Configures a Domain Message Handler
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return DomainMessageHandlerConfigurator
     */
    protected function messageHandler(string $serviceId, ?string $serviceClass = null): DomainMessageHandlerConfigurator
    {
        $conf = new DomainMessageHandlerConfigurator(
            $this->container,
            $this->services->set($serviceId, $serviceClass),
            $serviceId
        );
        $conf->public()->lazy()->autoconfigure()->autowire();
        return $conf;
    }

    /**
     * Configures a Domain Message Validator.
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return ServiceConfigurator
     */
    public function messageValidator(string $serviceId, ?string $serviceClass = null): ServiceConfigurator
    {
        return $this->service($serviceId, $serviceClass)->tag(self::DOMAIN_MESSAGE_VALIDATOR);
    }

    /**
     * Configures a Domain Message Authorizer.
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return ServiceConfigurator
     */
    public function messageAuthorizer(string $serviceId, ?string $serviceClass = null): ServiceConfigurator
    {
        return $this->service($serviceId, $serviceClass)->tag(self::DOMAIN_MESSAGE_AUTHORIZER);
    }

    /**
     * Registers an upcaster
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return ServiceConfigurator
     */
    public function upcaster(string $serviceId, ?string $serviceClass = null): ServiceConfigurator
    {
        return $this->services->set($serviceId, $serviceClass)->tag(self::UPCASTER_TAG);
    }

    /**
     * Registers a projector
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return ServiceConfigurator
     */
    public function projector(string $serviceId, ?string $serviceClass = null): ServiceConfigurator
    {
        return $this->services->set($serviceId, $serviceClass)->tag(self::PROJECTOR_TAG);
    }

    /**
     * Configures a console command.
     * @param string $className
     */
    public function consoleCommand(string $className): ServiceConfigurator
    {
        return $this->services
            ->set($className)
            ->tag('console.command')
            ;
    }

    /**
     * Registers a controller.
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return ServiceConfigurator
     */
    public function controller(string $serviceId, ?string $serviceClass = null): ServiceConfigurator
    {
        return $this->services
            ->set($serviceId, $serviceClass)
            ->tag('controller.service_arguments');
    }

    /**
     * Registers a service
     * @param string $serviceId
     * @param string|null $serviceClass
     * @return ServiceConfigurator
     */
    public function service(string $serviceId, ?string $serviceClass = null): ServiceConfigurator
    {
        return $this->services->set($serviceId, $serviceClass);
    }

    public function services(): ServicesConfigurator
    {
        return $this->services;
    }

    public function parameters(): ParametersConfigurator
    {
        return $this->container->parameters();
    }
}
