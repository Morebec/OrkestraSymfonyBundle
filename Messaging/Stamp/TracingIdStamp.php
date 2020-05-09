<?php

namespace Morebec\Monito\OrkestraBundle\Messaging\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * This stamp is used for the tracing middleware.
 */
class TracingIdStamp implements StampInterface
{
    /**
     * @var string
     */
    private $tracingId;

    /**
     * @var int
     */
    private $index;

    public function __construct(string $tracingId = null, int $index = 0)
    {
        $this->tracingId = $tracingId ?? uniqid('', true);
        $this->index = $index;
    }

    /**
     * Returns a stamp for a root trace.
     *
     * @return static
     */
    public static function asRoot(): self
    {
        return new static();
    }

    /**
     * Returns a stamp using a parent stamp as a base.
     *
     * @return static
     */
    public static function fromParent(self $stamp): self
    {
        return new static($stamp->getTracingId(), $stamp->getIndex() + 1);
    }

    public function getTracingId(): string
    {
        return $this->tracingId;
    }

    public function getIndex(): int
    {
        return $this->index;
    }
}
