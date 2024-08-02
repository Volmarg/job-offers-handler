<?php

namespace JobSearcher\RabbitMq\Producer;

use Doctrine\ORM\EntityManagerInterface;
use JobSearcher\Constants\RabbitMq\Common\CommunicationConstants;
use JobSearcher\Enum\RabbitMq\ConnectionTypeEnum;
use JobSearcher\RabbitMq\Connection\QueueConnectionNames;
use JobSearcher\Service\Storage\AmqpStorageService;
use LogicException;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Psr\Log\LoggerInterface;

/**
 * Provides some fixes / pre-configuration for the {@see Producer}
 */
abstract class BaseProducer extends Producer
{
    /**
     * @return string
     */
    abstract public function getTargetQueueName(): string;

    /**
     * There is some issue with `name` & `type` keys missing in the producing process
     * which is the package issue itself, so this fixes it,
     *
     * Atm. it's unknown what the keys really are for
     */
    public function fixExchangeOptions(): void
    {
        if (empty($this->getTargetQueueName())) {
            throw new LogicException("Target queue name is missing!");
        }

        QueueConnectionNames::isQueueSupported($this->getTargetQueueName());

        $this->exchangeOptions['name'] = $this->getTargetQueueName();
        $this->exchangeOptions['type'] = ConnectionTypeEnum::DIRECT->value;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param AmqpStorageService     $amqpStorageService
     * @param LoggerInterface        $aqmpLogger
     * @param AbstractConnection     $conn
     * @param AMQPChannel|null       $ch
     * @param null                   $consumerTag
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AmqpStorageService     $amqpStorageService,
        private readonly LoggerInterface        $aqmpLogger,
        AbstractConnection                      $conn,
        AMQPChannel                             $ch = null,
                                                $consumerTag = null,
    )
    {
        $this->fixExchangeOptions();
        parent::__construct($conn, $ch, $consumerTag);
    }

    /**
     * {@inheritDoc}
     */
    public function publish($msgBody, $routingKey = null, $additionalProperties = [], array $headers = null)
    {
        if (empty($routingKey)) {
            throw new LogicException("Even tho th routing key is allowed to be null, THIS project requires it to be set!");
        }

        $uniqueId        = uniqid();
        $modifiedMessage = $this->appendBaseKeys($uniqueId, $msgBody);

        $this->aqmpLogger->debug("Publishing message", [
            "class"      => self::class,
            "message"    => $msgBody,
            "routingKey" => $routingKey,
            "properties" => $additionalProperties,
            "headers"    => $headers,
        ]);

        $this->handleStorageEntry($modifiedMessage, $uniqueId);
        parent::publish($msgBody, $routingKey, $additionalProperties, $headers);
    }

    /**
     * Will attach some base keys to each "produce" call:
     *
     * @param string $uniqueId
     * @param string $message
     *
     * @return string
     */
    private function appendBaseKeys(string $uniqueId, string $message): string
    {
        json_decode($message);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new LogicException("Received message is not a valid json. Message: {$message}, json error: " . json_last_error_msg());
        }

        $dataArray = json_decode($message, true);
        $dataArray[CommunicationConstants::KEY_UNIQUE_ID] = $uniqueId;

        return json_encode($dataArray);
    }

    /**
     * Will handle the saving the AQMP entry in db
     *
     * @param string $messageBody
     * @param string $uniqueId
     *
     * @return void
     */
    private function handleStorageEntry(string $messageBody, string $uniqueId): void
    {
        $storageEntity = $this->amqpStorageService->createFromAmqpMessage(
            $messageBody,
            static::class,
            $uniqueId,
        );

        $this->entityManager->persist($storageEntity);
        $this->entityManager->flush();
    }

}