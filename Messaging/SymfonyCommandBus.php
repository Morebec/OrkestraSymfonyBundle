<?php

namespace Morebec\OrkestraBundle\Messaging;

use Morebec\Orkestra\Messaging\Command\CommandBusInterface;
use Morebec\Orkestra\Messaging\Command\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class SymfonyCommandBus
 * A Command bus based on Symfony's Messenger Component.
 */
class SymfonyCommandBus implements CommandBusInterface
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->bus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(CommandInterface $command)
    {
        try {
            $this->bus->dispatch($command);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious();
        }
    }
}
