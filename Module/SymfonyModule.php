<?php

namespace Morebec\OrkestraBundle\Module;

use Morebec\Orkestra\Messaging\Command\CommandBusInterface;
use Morebec\Orkestra\Messaging\Command\CommandInterface;
use Morebec\Orkestra\Messaging\Event\EventBusInterface;
use Morebec\Orkestra\Messaging\Event\EventInterface;
use Morebec\Orkestra\Messaging\Notification\NotificationBusInterface;
use Morebec\Orkestra\Messaging\Notification\NotificationInterface;
use Morebec\Orkestra\Messaging\Query\QueryBusInterface;
use Morebec\Orkestra\Messaging\Query\QueryInterface;
use Morebec\Orkestra\Module\ModuleInterface;

/**
 * Orkestra Module implementation using Symfony.
 */
class SymfonyModule implements ModuleInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var QueryBusInterface
     */
    private $queryBus;

    /**
     * @var NotificationBusInterface
     */
    private $notificationBus;

    /**
     * @var CommandBusInterface
     */
    private $resultingCommandBus;

    public function __construct(
        CommandBusInterface $commandBus,
        CommandBusInterface $resultingCommandBus,
        EventBusInterface $eventBus,
        QueryBusInterface $queryBus,
        NotificationBusInterface $notificationBus
    ) {
        $this->commandBus = $commandBus;
        $this->resultingCommandBus = $resultingCommandBus;
        $this->eventBus = $eventBus;
        $this->queryBus = $queryBus;
        $this->notificationBus = $notificationBus;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command): void
    {
        $this->commandBus->dispatch($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executingResultingCommand(CommandInterface $command)
    {
        return $this->resultingCommandBus->dispatch($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery(QueryInterface $query)
    {
        return $this->queryBus->dispatch($query);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchEvent(EventInterface $event): void
    {
        $this->eventBus->dispatch($event);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchNotification(NotificationInterface $notification): void
    {
        $this->notificationBus->dispatch($notification);
    }
}
