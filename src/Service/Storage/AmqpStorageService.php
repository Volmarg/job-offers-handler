<?php

namespace JobSearcher\Service\Storage;

use JobSearcher\Entity\Storage\AmqpStorage;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Service for {@see AmqpStorage}
 */
class AmqpStorageService
{
    public function __construct(){}

    /**
     * Will create {@see AmqpStorage} from {@see AMQPMessage}
     *
     * @param string      $messageBody
     * @param string      $targetClass
     * @param string|null $uniqueId
     *
     * @return AmqpStorage
     */
    public function createFromAmqpMessage(string $messageBody, string $targetClass, ?string $uniqueId = null): AmqpStorage
    {
        $storageEntry = new AmqpStorage();
        $storageEntry->setMessage($messageBody);
        $storageEntry->setTargetClass($targetClass);

        if (!empty($uniqueId)) {
            $storageEntry->setUniqueId($uniqueId);
        }

        return $storageEntry;
    }

}