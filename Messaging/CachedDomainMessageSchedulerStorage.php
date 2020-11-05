<?php

namespace Morebec\OrkestraSymfonyBundle\Messaging;

use Morebec\Orkestra\DateTime\DateTime;
use Morebec\Orkestra\Messaging\DomainMessageHeaders;
use Morebec\Orkestra\Messaging\Scheduling\DomainMessageSchedulerStorageInterface;
use Morebec\Orkestra\Messaging\Scheduling\ScheduledDomainMessageWrapper;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Implementation of the {@Link DomainMessageSchedulerStorageInterface} that stores messages in memory
 * and to cache.
 * This is only for demonstration purposes and should not be used in production since the data is
 * never actually stored.
 * Instead a database or file system backed adapter is recommended.
 */
class CachedDomainMessageSchedulerStorage implements DomainMessageSchedulerStorageInterface
{
    private const CACHE_KEY = 'scheduled_domain_messages';

    /**
     * @var ScheduledDomainMessageWrapper[]
     */
    private $messages;
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->messages = $cache->get(self::CACHE_KEY, static function (ItemInterface $item) {
            return [];
        });
        $this->cache = $cache;
    }

    public function add(ScheduledDomainMessageWrapper $wrappedMessage): void
    {
        $this->messages[$wrappedMessage->getMessageId()] = $wrappedMessage;


        $this->cache->delete(self::CACHE_KEY);

        $messages = $this->messages;
        $this->cache->get(self::CACHE_KEY, static function (ItemInterface $item) use ($messages) {
            $item->set($messages);
            return $messages;
        });
    }

    public function findScheduledBefore(DateTime $dateTime): array
    {
        $messages = [];
        foreach ($this->messages as $wrapper) {
            $headers = $wrapper->getMessageHeaders();
            $scheduledAt = DateTime::createFromFormat('U.u', (string)$headers->get(DomainMessageHeaders::SCHEDULED_AT));
            if ($scheduledAt->isBefore($dateTime) || $scheduledAt->equals($dateTime)) {
                $messages[] = $wrapper;
            }
        }

        return $messages;
    }

    public function findByDateTime(DateTime $from, DateTime $to): array
    {
        $messages = [];
        foreach ($this->messages as $wrapper) {
            $headers = $wrapper->getMessageHeaders();
            $scheduledAt = DateTime::createFromFormat('U.u', (string)$headers->get(DomainMessageHeaders::SCHEDULED_AT));
            if ($scheduledAt->isBetween($from, $to, true)) {
                $messages[] = $wrapper;
            }
        }

        return $messages;
    }

    public function remove(ScheduledDomainMessageWrapper $message): void
    {
        unset($this->messages[$message->getMessageId()]);

        $this->cache->delete(self::CACHE_KEY);

        $messages = $this->messages;
        $this->cache->get(self::CACHE_KEY, static function (ItemInterface $item) use ($messages) {
            $item->set($messages);
            return $messages;
        });
    }
}