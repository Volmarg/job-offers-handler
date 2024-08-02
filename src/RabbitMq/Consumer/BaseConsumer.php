<?php

namespace JobSearcher\RabbitMq\Consumer;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Entity\Storage\AmqpStorage;
use JobSearcher\Service\Storage\AmqpStorageService;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Provides some fixes / pre-configuration for the {@see Consumer}
 */
abstract class BaseConsumer implements ConsumerInterface
{
    private const MESSAGE_DELAY = 10; // seconds

    public function __construct(
        private readonly AmqpStorageService       $amqpStorageService,
        protected readonly EntityManagerInterface $entityManager,
        protected LoggerInterface                 $amqpLogger
    ) {
    }

    /**
     * Handles the execution of the consumer code
     *
     * @param AMQPMessage $msg
     * @param AmqpStorage $amqpStorageEntity
     *
     * @return int
     */
    public abstract function doExecute(AMQPMessage $msg, AmqpStorage $amqpStorageEntity): int;

    /**
     * Cannot use transaction in here!
     *
     * Because IF the executed command calls some logic which tries to bind the entry to {@see AmqpStorage} then
     * it would fail because the transaction would still be not committed and so the mentioned {@see AmqpStorage} would not exist.
     *
     * {@inheritDoc}
     */
    public function execute(AMQPMessage $msg): int
    {

        try {
            $storageEntity = $this->beforeExecute($msg);
            if (empty($storageEntity)) {
                $this->amqpLogger->critical("Could not create storage entity for message, rejecting it and re-queueing", [
                    "message" => $msg,
                ]);

                sleep(self::MESSAGE_DELAY);
                return ConsumerInterface::MSG_REJECT_REQUEUE;
            }

            $responseCode = $this->doExecute($msg, $storageEntity);
        } catch (Exception|TypeError $e) {
            $this->amqpLogger->critical("Got exception in: " . static::class, [
                "exception" => [
                    "class"   => $e::class,
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ],
                "info" => [
                    "what-now" => "Re-queueing the rabbit message",
                    "message"  => $msg->getBody()
                ]
            ]);

            sleep(self::MESSAGE_DELAY);
            return ConsumerInterface::MSG_REJECT_REQUEUE;
        }

        return $responseCode;
    }

    /**
     * If everything is ok then {@see AmqpStorage} entity is returned, null otherwise
     *
     * @param AMQPMessage $msg
     *
     * @return AmqpStorage|null
     */
    private function beforeExecute(AMQPMessage $msg): ?AmqpStorage
    {
        json_decode($msg->getBody());
        if (JSON_ERROR_NONE !== json_last_error()) {
            return null;
        }

        $storageEntity = $this->amqpStorageService->createFromAmqpMessage($msg->getBody(), static::class);

        $this->entityManager->persist($storageEntity);
        $this->entityManager->flush();

        return $storageEntity;
    }

}