<?php

namespace Morebec\OrkestraBundle\Messaging;

use Morebec\Orkestra\Messaging\Notification\NotificationBusInterface;
use Morebec\Orkestra\Messaging\Notification\NotificationInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Class SymfonyNotificationBus
 * Notification bus based on Symfony.
 */
class SymfonyNotificationBus implements NotificationBusInterface
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(MessageBusInterface $eventBus)
    {
        $this->bus = $eventBus;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(NotificationInterface $notification): void
    {
        $this->bus->dispatch((new Envelope($notification))->with(new DispatchAfterCurrentBusStamp()));
    }
}
