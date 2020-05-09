<?php

namespace Morebec\OrkestraBundle\Resources\config;

use Morebec\OrkestraBundle\Console\ProcessEventQueueConsoleCommand;
use Morebec\OrkestraBundle\Messaging\Middleware\AuditMessageMiddleware;
use Morebec\OrkestraBundle\Messaging\Middleware\HandleMessageAndAllowNoHandlerMiddleware;
use Morebec\OrkestraBundle\Messaging\Middleware\HandleMessageMiddleware;
use Morebec\OrkestraBundle\Messaging\SymfonyCommandBus;
use Morebec\OrkestraBundle\Messaging\SymfonyEventBus;
use Morebec\OrkestraBundle\Messaging\SymfonyNotificationBus;
use Morebec\OrkestraBundle\Messaging\SymfonyQueryBus;
use Morebec\Orkestra\Adapter\MongoDB\MongoDBClient;
use Morebec\Orkestra\Adapter\MongoDB\MongoDBEventStore;
use Morebec\Orkestra\Adapter\MongoDB\MongoDBEventStoreTrackingUnitRepository;
use Morebec\Orkestra\Adapter\MongoDB\MongoDBWorkflowStateRepository;
use Morebec\Orkestra\EventSourcing\AggregateRootEventStore;
use Morebec\Orkestra\EventSourcing\AggregateRootEventStoreInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreTracker;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreTrackingUnitRepositoryInterface;
use Morebec\Orkestra\EventSourcing\EventStoreChaser;
use Morebec\Orkestra\Messaging\Command\CommandBusInterface;
use Morebec\Orkestra\Messaging\Event\EventBusInterface;
use Morebec\Orkestra\Messaging\Notification\NotificationBusInterface;
use Morebec\Orkestra\Messaging\Query\QueryBusInterface;
use Morebec\Orkestra\Workflow\WorkflowStateRepositoryInterface;
use Symfony\Bridge\Monolog\Logger as MonologLogger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();
    $services
        ->defaults()
            ->private()
            ->autoconfigure()
            ->autowire()
    ;

    // Messages
    $services->set(CommandBusInterface::class, SymfonyCommandBus::class)
        ->alias('orkestra.command_bus', CommandBusInterface::class);
    $services->set(QueryBusInterface::class, SymfonyQueryBus::class)
        ->alias('orkestra.query_bus', QueryBusInterface::class);
    $services->set(EventBusInterface::class, SymfonyEventBus::class)
        ->alias('orkestra.event_bus', QueryBusInterface::class);
    $services->set(NotificationBusInterface::class, SymfonyNotificationBus::class)
        ->alias('orkestra.notification_bus', NotificationBusInterface::class);

    $services->set(MessageHandlerProvider::class);
    $services->set(MessageHandlerMap::class);
    $services->set(MessageHandlerResolver::class);

    // Messenger Middleware
    $services->set('orkestra.audit_middleware', AuditMessageMiddleware::class);
    $services->set('orkestra.handle_message_middleware', HandleMessageMiddleware::class);
    $services->set('orkestra.handle_message_and_allow_no_handler_middleware', HandleMessageAndAllowNoHandlerMiddleware::class);

    $services->set('orkestra.audit_logger', MonologLogger::class)
        ->arg('$name', 'messenger_audit')
        ->share()
        ->tag('monolog.logger', ['channel' => 'messenger_audit'])
    ;

    // Console Commands
    $services->set(ProcessEventQueueConsoleCommand::class);

    // Event Sourcing
    $services->set(EventStoreChaser::class);
    $services->set(AggregateRootEventStoreInterface::class);
    $services->set(EventStoreTracker::class);
    $services->set(EventStoreTrackingUnitRepositoryInterface::class);
    $services->set(AggregateRootEventStoreInterface::class, AggregateRootEventStore::class);

    // MONGODB ADAPTER SUPPORT
    // MongoDB Event Store implementation
    if (class_exists(MongoDBEventStore::class)) {
        $services->set(EventStoreInterface::class, MongoDBEventStore::class);
        $services->set(MongoDBClient::class)->args(['%env(MONGODB_URL)%', '%env(MONGODB_DB)%']);
        $services->set(WorkflowStateRepositoryInterface::class, MongoDBWorkflowStateRepository::class);
        $services->set(EventStoreTrackingUnitRepositoryInterface::class, MongoDBEventStoreTrackingUnitRepository::class);
    }
};
