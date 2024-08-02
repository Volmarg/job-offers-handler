<?php

namespace JobSearcher\RabbitMq\Consumer;

use JobSearcher\Entity\Storage\AmqpStorage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class TestConsumer extends BaseConsumer
{
    /**
     * {@inheritDoc}
     */
    public function doExecute(AMQPMessage $msg, AmqpStorage $amqpStorageEntity): int
    {
        $this->amqpLogger->critical($msg->getBody());

        return ConsumerInterface::MSG_ACK;
    }
}