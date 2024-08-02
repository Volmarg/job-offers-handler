<?php

namespace JobSearcher\Service\RabbitMq\JobSearcher;

use JobSearcher\Constants\RabbitMq\Common\CommunicationConstants;
use JobSearcher\RabbitMq\Producer\JobSearch\JobSearchDoneProducer;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the {@see JobSearchProducer}, this is most like a wrapper, for providing the message
 */
class JobSearchDoneProducerService
{
    public const KEY_EXTRACTION_ID     = "extractionId";
    public const KEY_EXTRACTION_STATUS = "extractionStatus";
    public const KEY_PERCENTAGE_DONE = "percentageDone";

    public function __construct(
        private readonly JobSearchDoneProducer $jobSearchDoneProducer
    ) {
    }

    /**
     * @param string   $incomingMessageId
     * @param int|null $extractionId
     * @param int      $searchId
     * @param bool     $isSuccess
     * @param string   $extractionStatus
     * @param float    $percentageDone
     */
    public function produce(
        string $incomingMessageId,
        ?int   $extractionId,
        int    $searchId,
        bool   $isSuccess,
        string $extractionStatus,
        float  $percentageDone
    ): void
    {
        $request = Request::createFromGlobals();

        $message = json_encode([
            CommunicationConstants::KEY_SUCCESS            => $isSuccess,
            CommunicationConstants::KEY_RECEIVED_UNIQUE_ID => $incomingMessageId,
            CommunicationConstants::KEY_SEARCH_ID          => $searchId,
            CommunicationConstants::KEY_HOST_WITH_PORT     => $request->getHttpHost(),
            CommunicationConstants::KEY_IP                 => $request->getClientIp(),
            self::KEY_EXTRACTION_ID                        => $extractionId,
            self::KEY_EXTRACTION_STATUS                    => $extractionStatus,
            self::KEY_PERCENTAGE_DONE                      => $percentageDone
        ]);

        $this->jobSearchDoneProducer->publish($message);
    }

}