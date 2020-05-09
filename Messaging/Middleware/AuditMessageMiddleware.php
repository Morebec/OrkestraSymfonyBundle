<?php

namespace Morebec\OrkestraBundle\Messaging\Middleware;

use Morebec\OrkestraBundle\Messaging\Stamp\TracingIdStamp;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * This middleware is used to create a trail for messages with a correlation id (trace_id) pointing to the
 * first message dispatched.
 * It is used for auditing purposes to know what are the side effects of a given message.
 */
class AuditMessageMiddleware implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var \SplStack */
    private $traceStack;

    private $rootStamp;

    public function __construct(LoggerInterface $messengerAuditLogger)
    {
        $this->logger = $messengerAuditLogger;
        $this->traceStack = new \SplStack();
    }

    public function temp()
    {
        $envelope = null;
        // Add tracing data
        /*$stamp = $envelope->last(TracingIdStamp::class);
        $this->transactionStarted = $stamp === null;
        if ($this->transactionStarted) {
            $envelope = $envelope->with(TracingIdStamp::asRoot());
        } else ($stamp->getIndex() !== 0) {
            $envelope = $envelope->with(TracingIdStamp::fromParent($stamp));
        }*/
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Messages when using a transport go through the bus twice:
        // (Sending Phase) First, during the dispatching (to be sent to the transport)
        // (Receiving Phase) And second, when it is received from the transport and ready to be handled.
        // This means that whatever we do before passing the envelope to the next handlers will happen twice.
        // The same holds true for whatever we do after.
        // We must take that into account as to not log everything twice

        $message = $envelope->getMessage();

        // To know the tracing stamp to use we must determine:
        // if we are in the root message
        // Or a nested message.
        // If the stamp already has a stamp, we are not the root
        /** @var TracingIdStamp $currentEnvelopeStamp */
        $currentEnvelopeStamp = $envelope->last(TracingIdStamp::class);

        if (!$currentEnvelopeStamp) {
            if ($this->rootStamp) {
                $currentEnvelopeStamp = TracingIdStamp::fromParent($this->rootStamp);
            } else {
                // We are in the root message
                $this->rootStamp = TracingIdStamp::asRoot();
                $currentEnvelopeStamp = $this->rootStamp;
            }
            $envelope = $envelope->with($currentEnvelopeStamp);
        }

        $context = [
            'trace_id' => $currentEnvelopeStamp->getTracingId(),
            'trace_index' => $currentEnvelopeStamp->getIndex(),
            'short_class' => (new \ReflectionClass($message))->getShortName(),
            'class' => \get_class($message),
        ];

        // The condition must be this way, it is the only way of testing the Sending or receiving phase
        $sending = false;
        if (!$envelope->all(ReceivedStamp::class)) {
            $sending = true;
            $this->logger->info('[{trace_id}:{trace_index}] Sending message {short_class} ...', $context);
        } else {
            $this->logger->info('[{trace_id}:{trace_index}] Received {short_class}', $context);
            $this->logger->info('[{trace_id}:{trace_index}] Handling {short_class} ...', $context);
        }

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (\Exception $e) {
            $exception = $e instanceof HandlerFailedException ? $e->getPrevious() : $e;
            $context['error'] = $exception;
            $context['error_message'] = $exception->getMessage();
            $context['error_code'] = $exception->getMessage();
            $context['error_file'] = $exception->getFile();
            $context['error_file_line'] = $exception->getLine();

            $this->logger->error('Failed {short_class}', $context);
            throw $e;
        }

        if ($envelope->last(HandledStamp::class) && $sending) {
            $this->logger->info('[{trace_id}:{trace_index}] Handled {short_class}: ', $context);
        }

        // Cleanup
        if ($currentEnvelopeStamp === $this->rootStamp) {
            $this->rootStamp = null;
        }

        return $envelope;
    }
}
