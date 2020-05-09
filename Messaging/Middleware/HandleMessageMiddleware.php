<?php

namespace Morebec\OrkestraBundle\Messaging\Middleware;

use Morebec\Orkestra\Messaging\MessageHandlerMap;
use Morebec\Orkestra\Messaging\MessageHandlerProvider;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

class HandleMessageMiddleware implements MiddlewareInterface
{
    /**
     * @var MessageHandlerProvider
     */
    private $handlerProvider;

    /**
     * @var \Morebec\Orkestra\Messaging\MessageHandlerMap
     */
    private $handlerMap;
    /**
     * @var bool
     */
    private $allowNoHandler;

    public function __construct(
        MessageHandlerProvider $handlerProvider,
        MessageHandlerMap $handlerMap,
        bool $allowNoHandler = false
    ) {
        $this->handlerProvider = $handlerProvider;
        $this->handlerMap = $handlerMap;

        $this->allowNoHandler = $allowNoHandler;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NoHandlerForMessageException When no handler is found and $allowNoHandlers is false
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Find the bus of this middleware
        /** @var BusNameStamp $busNameStamp */
        $busNameStamp = $envelope->last(BusNameStamp::class);
        $busId = $busNameStamp->getBusName();

        $message = $envelope->getMessage();
        $messageClass = \get_class($message);

        $context = [
            'message' => $message,
            'class' => $messageClass,
        ];

        $exceptions = [];

        $handlers = [];
        foreach ($this->handlerMap->getHandlers($messageClass) as $handlerClassAndMethod) {
            [$handlerClass, $method] = explode('::', $handlerClassAndMethod);
            if ($this->hasMessageAlreadyBeenHandled($envelope, $handlerClass, $method)) {
                continue;
            }
            $handlers[$handlerClassAndMethod] = [$handlerClass, $method];
        }

        if (!$this->allowNoHandler && \count($handlers) === 0) {
            throw new NoHandlerForMessageException("No handler for message {$context['class']} on bus {$busId}");
        }

        foreach ($handlers as $handler) {
            [$handlerClass, $method] = $handler;

            try {
                /** @var object $handler */
                $handler = $this->handlerProvider->getHandler($handlerClass);
                $result = $handler->$method($message);
                $handledStamp = new HandledStamp($result, "{$handlerClass}::{$method}");
                $envelope = $envelope->with($handledStamp);
            } catch (Throwable $e) {
                $exceptions[] = $e;
            }
        }

        if (\count($exceptions)) {
            throw new HandlerFailedException($envelope, $exceptions);
        }

        return $stack->next()->handle($envelope, $stack);
    }

    /**
     * Indicates if a message has already been handled by a given handler's method.
     */
    private function hasMessageAlreadyBeenHandled(Envelope $envelope, string $handlerClass, string $method): bool
    {
        $some = array_filter(
            $envelope->all(HandledStamp::class),
            static function (HandledStamp $stamp) use ($handlerClass, $method) {
                return $stamp->getHandlerName() === ("{$handlerClass}::{$method}");
            }
        );

        return \count($some) > 0;
    }
}
