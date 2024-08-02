<?php

namespace JobSearcher\Service\RabbitMq;

use Exception;
use JobSearcher\Constants\RabbitMq\Common\CommunicationConstants;

class AmqpService
{

    /**
     * Will attempt to extract the RECEIVED uniqId from the message,
     * - returns `string` when uniqId is found,(the id)
     * - returns `null` if no key could get extracted,
     *
     * @param string $messageBody
     *
     * @return string|null
     * @throws Exception
     */
    public function extractIncomingMessageUniqueId(string $messageBody): ?string
    {
        $dataArray = json_decode($messageBody, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception("Provided message is not a valid json. Got message {$messageBody}. Json error" . json_last_error_msg());
        }

        /**
         * Because for this service the RECEIVED uniqId is the ID of the incoming message
         * The receivedId is what THAT MESSAGE received (which would be id from this side for example)
         */
        $receivedUniqId = $dataArray[CommunicationConstants::KEY_UNIQUE_ID] ?? null;
        return $receivedUniqId;
    }

}