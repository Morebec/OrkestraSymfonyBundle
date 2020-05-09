<?php

namespace Morebec\OrkestraBundle\Messaging;

use Morebec\Orkestra\Messaging\Event\EventBusInterface;
use Morebec\Orkestra\Messaging\Event\EventInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SymfonyEventBus implements EventBusInterface
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
    public function dispatch(EventInterface $event): void
    {
        // $this->bus->dispatch((new Envelope($event))->with(new DispatchAfterCurrentBusStamp()));
        $this->bus->dispatch($event);
    }
}
