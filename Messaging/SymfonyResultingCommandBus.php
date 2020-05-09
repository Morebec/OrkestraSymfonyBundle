<?php

namespace Morebec\OrkestraBundle\Messaging;

use Morebec\Orkestra\Messaging\Command\CommandBusInterface;
use Morebec\Orkestra\Messaging\Command\CommandInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

/**
 * Class SymfonyResultingCommandBus
 * An implementation of a Resulting Command bus based on Symfony's Messenger Component.
 */
class SymfonyResultingCommandBus implements CommandBusInterface
{
    use HandleTrait;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct()
    {
        $this->messageBus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                // TODO
            ])),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(CommandInterface $command)
    {
        return $this->handle($command);
    }
}
