<?php

namespace JobSearcher\Service\Extraction\Offer;

use Exception;
use JobSearcher\Action\API\Extraction\ExtractionController;
use JobSearcher\Constants\RabbitMq\Common\CommunicationConstants;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\Storage\AmqpStorage;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use JobSearcher\Repository\Storage\AmqpStorageRepository;
use JobSearcher\Response\Extraction\GetExtractionsMinimalDataResponse;
use JobSearcher\Service\RabbitMq\JobSearcher\JobSearchDoneProducerService;
use JobSearcher\Service\Symfony\LoggerService;
use LogicException;
use TypeError;

/**
 * Class exists for cases such like:
 * - {@see ExtractionController::getMinimalData()}
 */
class ApiOfferExtractionService
{
    public function __construct(
        private readonly JobOfferExtractionRepository $jobOfferExtractionRepository,
        private readonly AmqpStorageRepository        $amqpStorageRepository,
        private readonly LoggerService                $logger,
    ) {
    }

    /**
     * Strictly related to {@see ExtractionController::getMinimalData()}
     *
     * @param array  $extractionIds   - extractions ids stored in current project
     * @param array  $clientSearchIds - ids stored in external tool - these are NOT {@see JobOfferExtraction::getId()},
     *                                  but rather some ids used in tool that calls current project
     *
     * @param GetExtractionsMinimalDataResponse $response
     */
    public function setMinimalData(array $extractionIds, array $clientSearchIds, GetExtractionsMinimalDataResponse $response): void
    {
        $offersCountData = $this->jobOfferExtractionRepository->getFoundOffersCount($extractionIds);
        $extractions     = $this->jobOfferExtractionRepository->findBy([
            'id' => $extractionIds,
        ]);

        $statuses       = [];
        $percentageDone = [];
        foreach ($extractions as $extraction) {
            $statuses[$extraction->getId()]       = $extraction->getStatus();
            $percentageDone[$extraction->getId()] = $extraction->getPercentageDone();
        }

        $response->setFoundOffersCount($offersCountData);
        $response->setClientIdStatuses($statuses);
        $response->setClientIdPercentageDone($percentageDone);

        if (!empty($clientSearchIds)) {
            $this->addSearchIdsMinimalData($clientSearchIds, $response);
        }
    }

    /**
     * Will search for {@see JobOfferExtraction} data via {@see AmqpStorage}.
     * That's required as the searchId (client side based id) is not stored anywhere else.
     * That's the only way to get the necessary data.
     *
     * @param array                             $clientSearchIds
     * @param GetExtractionsMinimalDataResponse $response
     */
    private function addSearchIdsMinimalData(array $clientSearchIds, GetExtractionsMinimalDataResponse $response): void
    {
        $amqpStorageEntities = $this->amqpStorageRepository->findBySearchIds($clientSearchIds);

        $statuses              = [];
        $percentageDone        = [];
        $clientIdExtractionIds = [];
        foreach ($amqpStorageEntities as $storageEntity) {
            try {
                $messageData = json_decode($storageEntity->getMessage(), true);

                $extractionId = $messageData[JobSearchDoneProducerService::KEY_EXTRACTION_ID];
                if (empty($extractionId)) {
                    throw new LogicException("Got amqp storage entity without extraction id");
                }

                $clientSearchId                  = $messageData[CommunicationConstants::KEY_SEARCH_ID];
                $percentageDone[$clientSearchId] = $messageData[JobSearchDoneProducerService::KEY_PERCENTAGE_DONE];
                $statuses[$clientSearchId]       = $messageData[JobSearchDoneProducerService::KEY_EXTRACTION_STATUS];

                $clientIdExtractionIds[$clientSearchId] = $extractionId;
            } catch (Exception|TypeError $e) {
                $this->logger->logException($e, [
                    "storageEntity" => $storageEntity->getId(),
                ]);
            }
        }

        $offersCountData = $this->jobOfferExtractionRepository->getFoundOffersCount($clientIdExtractionIds);

        $response->addFoundOffersCount($offersCountData);
        $response->addClientIdPercentageDone($percentageDone);
        $response->addClientIdStatuses($statuses);
        $response->addClientIdExtractionId($clientIdExtractionIds);
    }
}