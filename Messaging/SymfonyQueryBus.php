<?php

namespace Morebec\OrkestraBundle\Messaging;

use Morebec\Orkestra\Messaging\Query\QueryBusInterface;
use Morebec\Orkestra\Messaging\Query\QueryInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class SymfonyQueryBus
 * A Query Bus based on Symfony's messenger component.
 */
class SymfonyQueryBus implements QueryBusInterface
{
    use HandleTrait;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(QueryInterface $query)
    {
        return $this->handle($query);
    }
}
