# Orkestra Symfony Bundle
This bundle integrates the Orkestra Framework with Symfony 5.

[![Build Status](https://travis-ci.com/Morebec/OrkestraSymfonyBundle.svg?branch=v1.x)](https://travis-ci.com/Morebec/OrkestraSymfonyBundle)

## Installation

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require morebec/orkestra-symfony-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require morebec/orkestra-symfony-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
return [
    // ...
    OrkestraSymfonyBundle::class => ['all' => true]
];

```

#### Step 3: Add an Adapter
For persistence and infrastructure concerns, Orkestra requires adapters.

Install one of the adapters and register the classes of the adapter as services in a Module Configurator 
(see below for more information).
 
## Usage

### Creating a Module for a Bounded Context
A Module is a logical separation of the source code. This is usually linked to the separations of DDD Bounded Contexts 
according to the context map.
Although Symfony provides a Bundle System, This bundle's Module System is tailored for the dependency injection needs  of Orkestra
based application. It provides ways to configure services using pure PHP with a fluent API which simplifies greatly this process
while still allowing all the power of Symfony.

#### Step 1: Create a configuration class for the Module
1. Create a directory under `src` with the name of your Module. E.g. `Shipping'.
2. Inside this directory, create a class implementing the `SymfonyOrkestraModuleConfiguratorInterface`.
This class will be used by the bundle to register the service dependencies of the module with Symfony's service container
as well as the controller routes with the Symfony Route *(not to be confused with `DomainMessageRoutes`)*.
```php
class SandboxModuleConfiguratorConfigurator implements SymfonyOrkestraModuleConfiguratorInterface
{
    public function configureContainer(ContainerConfigurator $container): void
    {
        $conf = new SymfonyOrkestraModuleContainerConfigurator($container);

        $conf->services()
            ->defaults()
            ->autowire()
            ->autoconfigure()
        ;

        $conf->commandHandler(SandBoxMessageHandler::class)
            ->autoroute()
            ->disableMethodRoute('__invoke')
        ;

        $conf->consoleCommand(SandboxConsoleCommand::class);

    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
    }
}
```
> Note: The bundle provides a class `SymfonyOrkestraModuleContainerConfigurator` that allows to fluently define services.
> With a language closer to the technical requirements of Orkestra (eventHandler, commandHandler, queryHandler projectors etc.)

#### Step 2: Enable the Module
Then, enable the module by adding its Configurator to the list of registered Module Configurators in the `config/modules.php` file of your project:
```php
return [
    // ...
    SandboxModuleConfiguratorConfigurator::class => ['all' => true],
];
``` 
> Module Configurations are registered just like Symfony Bundles allowing you to provide the environment in which they should exist.
> If you need different configurator on a per environment basis you can simply check for the environment using `$_ENV['APP_ENV]` in the configurators code.

#### Step 3: Configure Adapter
As previously explained Orkestra requires adapters in order to support infrastructure concerns.
There adapters are not configured by this bundle since they might be various. Also, it does not forces to use an Event Store
by default, meaning this should be configured.

Here's an example configuration for the MongoDB Adapter:

```php
$configurator = new SymfonyOrkestraModuleContainerConfigurator($container);
$configurator->service(MongoDbClient::class)->args(['%env(MONGO_URL)%', '%env(MONGO_DATABASE)%']);
$configurator->service(MongoDbTransactionMiddleware::class);
$configurator->service(DomainMessageSchedulerStorageInterface::class, MongoDbDomainMessageSchedulerStorage::class);
$configurator->service(PersonalInformationStoreInterface::class, MongoDbPersonalInformationStore::class);
$configurator->service(EventStoreInterface::class, SimpleEventStore::class);
$configurator->service(SimpleEventStorageReaderInterface::class, MongoDbSimpleEventStoreStorage::class);
$configurator->service(SimpleEventStorageWriterInterface::class, MongoDbSimpleEventStoreStorage::class);
$configurator->service(ProjectorStateStorageInterface::class, MongoDbProjectorStateStorage::class);
```

> Note that the use of the `SymfonyOrkestraModuleContainerConfigurator` is optional and was just for demonstration purposes.

### Adding Compiler Passes
The module configurator do not have a specific method to add compiler passes, instead, you can rely on the `ContainerConfigurator`
to register custom `Container Extensions`. For more information on this please refer to the Official Symfony Documentation.

### Configuring the Domain Message Bus
The `DomainMessageBusInterface` can be configured to receive more Middleware.
This Bundle provides a `DefaultDomainMessageBusConfigurator` that sets up the default Orkestra Middleware on the domain message bus.
To change the way it is wired, you can simply extend this class and register it in your dependency injection service configuration:
```php
// ...
$container
    ->services()
    ->set(DomainMessageBusConfiguratorInterface::class, YourCustomConfigurator::class);
```

#### Configuring the Domain Message Router
Configuring the router allows you to manually add new routes to the `DomainMessageRouterInterface`.
Simply create a new class implementing `DomainMessageRouterConfiguratorInterface` registered with the service container
and add your routes in the `configure` method. 

If you use the `SymfonyOrkestraModuleContainerConfigurator` routes can be automatically added to the bus while registering
message handlers.

#### Configuring the Domain Message Normalizer
The message bus can be configured to receive more `NormalizerInterface` and `DenormalizerInterface` as per your needs.
Simply create a new class implementing `DomainMessageNormalizerConfiguratorInterface` registered with the service container 
and add your `NormalizerInterface` and `DenormalizerInterface` in the `configure` method.
