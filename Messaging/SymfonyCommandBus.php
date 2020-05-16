<?php

namespace Morebec\OrkestraBundle\Messaging;

use Morebec\Orkestra\Messaging\Command\CommandBusInterface;
use Morebec\Orkestra\Messaging\Command\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

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
            $enveloppe = $this->bus->dispatch($command);
            /** @var HandledStamp $stamp */
            $stamp = $enveloppe->last(HandledStamp::class);
            if(!$stamp) {
                return null;
            }
            return $stamp->getResult();
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious();
        }
    }
}
