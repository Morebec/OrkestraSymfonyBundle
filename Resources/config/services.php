<?php

use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\DateTime\SystemClock;
use Morebec\Orkestra\EventSourcing\EventProcessor\EventProcessorWorker;
use Morebec\Orkestra\EventSourcing\Projecting\EventStoreProjectionist;
use Morebec\Orkestra\EventSourcing\Projecting\ProjectionistInterface;
use Morebec\Orkestra\EventSourcing\Upcasting\UpcasterChain;
use Morebec\Orkestra\Messaging\Context\BuildDomainContextMiddleware;
use Morebec\Orkestra\Messaging\Context\DomainContextManager;
use Morebec\Orkestra\Messaging\Context\DomainContextManagerInterface;
use Morebec\Orkestra\Messaging\Context\DomainContextProvider;
use Morebec\Orkestra\Messaging\Context\DomainContextProviderInterface;
use Morebec\Orkestra\Messaging\DomainMessageBus;
use Morebec\Orkestra\Messaging\DomainMessageBusInterface;
use Morebec\Orkestra\Messaging\Middleware\LoggerMiddleware;
use Morebec\Orkestra\Messaging\Normalization\DomainMessageClassMap;
use Morebec\Orkestra\Messaging\Normalization\DomainMessageClassMapInterface;
use Morebec\Orkestra\Messaging\Normalization\DomainMessageNormalizer;
use Morebec\Orkestra\Messaging\Normalization\DomainMessageNormalizerInterface;
use Morebec\Orkestra\Messaging\Routing\ContainerDomainMessageHandlerProvider;
use Morebec\Orkestra\Messaging\Routing\DomainMessageHandlerProviderInterface;
use Morebec\Orkestra\Messaging\Routing\DomainMessageRouterInterface;
use Morebec\Orkestra\Messaging\Routing\HandleDomainMessageMiddleware;
use Morebec\Orkestra\Messaging\Routing\Tenant\TenantAwareDomainMessageRouter;
use Morebec\Orkestra\Messaging\Scheduling\DomainMessageScheduler;
use Morebec\Orkestra\Messaging\Scheduling\DomainMessageSchedulerInterface;
use Morebec\Orkestra\Messaging\Scheduling\DomainMessageSchedulerStorageInterface;
use Morebec\Orkestra\Messaging\Scheduling\DomainMessageSchedulerWorker;
use Morebec\Orkestra\Messaging\Scheduling\ScheduleDomainMessageMiddleware;
use Morebec\Orkestra\Normalization\ObjectNormalizer;
use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;
use Morebec\OrkestraSymfonyBundle\Command\DebugDomainMessageRouter;
use Morebec\OrkestraSymfonyBundle\Command\RunDomainMessageSchedulerWorkerConsoleCommand;
use Morebec\OrkestraSymfonyBundle\Command\RunEventProcessorWorkerConsoleCommand;
use Morebec\OrkestraSymfonyBundle\Command\RunProjectionistWorkerConsoleCommand;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\ChainedDomainMessageNormalizerConfigurator;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\ChainedDomainMessageRouterConfigurator;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\DefaultDomainMessageBusConfigurator;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\DomainMessageBusConfiguratorInterface;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\DomainMessageNormalizerConfiguratorInterface;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\DomainMessageRouterCache;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\DomainMessageRouterConfiguratorInterface;
use Morebec\OrkestraSymfonyBundle\DependencyInjection\SymfonyDomainMessageClassMapFactory;
use Morebec\OrkestraSymfonyBundle\EventSourcing\Projecting\ProjectorRegistry;
use Morebec\OrkestraSymfonyBundle\Messaging\CachedDomainMessageSchedulerStorage;
use Morebec\OrkestraSymfonyBundle\Module\SymfonyOrkestraModuleContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
    ;

    // GENERAL
    $services->set(ClockInterface::class, SystemClock::class);
    $services->set(ObjectNormalizerInterface::class, ObjectNormalizer::class);

    // MESSAGING
    $services->set(DomainMessageBusInterface::class, DomainMessageBus::class)
        ->configurator([service(DomainMessageBusConfiguratorInterface::class), 'configure']);
    $services->set(DomainMessageBusConfiguratorInterface::class);
    $services->set(DomainMessageBusConfiguratorInterface::class, DefaultDomainMessageBusConfigurator::class);

    $services->set(BuildDomainContextMiddleware::class);
    $services->set(DomainContextManagerInterface::class, DomainContextManager::class);
    $services->set(DomainContextProviderInterface::class, DomainContextProvider::class);


    $services->set(LoggerMiddleware::class)->tag('monolog.logger', ['channel' => 'domain']);


    $services->set(HandleDomainMessageMiddleware::class)->tag('monolog.logger', ['channel' => 'domain']);
    $services->set(DomainMessageHandlerProviderInterface::class, ContainerDomainMessageHandlerProvider::class);
    $services->set(DomainMessageRouterInterface::class, TenantAwareDomainMessageRouter::class)
        ->configurator([service(DomainMessageRouterConfiguratorInterface::class), 'configure'])
    ;
    $services->set(DomainMessageRouterConfiguratorInterface::class, ChainedDomainMessageRouterConfigurator::class)
        ->autowire(true);
    $services->set(DomainMessageRouterCache::class)->args(['%kernel.cache_dir%']);


    $services->set(ScheduleDomainMessageMiddleware::class)->tag('monolog.logger', ['channel' => 'domain']);
    $services->set(DomainMessageSchedulerInterface::class, DomainMessageScheduler::class);
    $services->set(DomainMessageSchedulerStorageInterface::class, CachedDomainMessageSchedulerStorage::class);

    // Upcasting
    $services->set(UpcasterChain::class)->args([tagged_iterator(SymfonyOrkestraModuleContainerConfigurator::UPCASTER_TAG)]);

    // Serialization
    $services->set(SymfonyDomainMessageClassMapFactory::class);
    $services->set(DomainMessageClassMapInterface::class, DomainMessageClassMap::class)
        ->factory([service(SymfonyDomainMessageClassMapFactory::class), 'buildClassMap']);
    $services->set(DomainMessageNormalizerInterface::class, DomainMessageNormalizer::class)
            ->configurator([service(DomainMessageNormalizerConfiguratorInterface::class), 'configure']);
    $services->set(DomainMessageNormalizerConfiguratorInterface::class, ChainedDomainMessageNormalizerConfigurator::class)
            ->autowire(false);


    // Projections
    $services->set(ProjectionistInterface::class, EventStoreProjectionist::class);
    $services->set(ProjectorRegistry::class)->args([tagged_iterator(SymfonyOrkestraModuleContainerConfigurator::PROJECTOR_TAG)]);

    // Console Commands
    $services->set(EventProcessorWorker::class);
    $services->set(RunEventProcessorWorkerConsoleCommand::class)->tag('console.command');

    $services->set(DomainMessageSchedulerWorker::class);
    $services->set(RunDomainMessageSchedulerWorkerConsoleCommand::class)->tag('console.command');


    $services->set(RunProjectionistWorkerConsoleCommand::class)->tag('console.command');
    $services->set(DebugDomainMessageRouter::class)->tag('console.command');
};