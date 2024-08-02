<?php

namespace JobSearcher\RabbitMq\Connection;

use LogicException;

/**
 * Provides available queues
 */
class QueueConnectionNames
{
    public const JOB_OFFERS_HANDLER_SEARCH_DONE = "job-offers-handler-search-done";

    public const ALL_QUEUES = [
        self::JOB_OFFERS_HANDLER_SEARCH_DONE,
    ];

    /**
     * Check if provided queue is supported, but it still might be not reachable if the consumer is running under
     * different name
     *
     * @param string $queueName
     */
    public static function isQueueSupported(string $queueName): void
    {
        if (!in_array($queueName, self::ALL_QUEUES)) {
            throw new LogicException("Queue named ${$queueName} is not supported");
        }
    }
}