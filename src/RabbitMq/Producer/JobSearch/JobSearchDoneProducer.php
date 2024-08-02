<?php

namespace JobSearcher\RabbitMq\Producer\JobSearch;

use JobSearcher\RabbitMq\Producer\BaseProducer;
use JobSearcher\RabbitMq\Connection\QueueConnectionNames;

/**
 * @description dummy test producer, nobody cares about the message. It's just for testing if producing works
 */
class JobSearchDoneProducer extends BaseProducer
{
    /**
     * {@inheritDoc}
     **/
    public function publish($msgBody, $routingKey = null, $additionalProperties = array(), array $headers = null): void
    {
        parent::publish($msgBody, QueueConnectionNames::JOB_OFFERS_HANDLER_SEARCH_DONE);
    }

    /**
     * @return string
     */
    public function getTargetQueueName(): string
    {
        return QueueConnectionNames::JOB_OFFERS_HANDLER_SEARCH_DONE;
    }

}