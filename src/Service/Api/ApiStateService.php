<?php

namespace JobSearcher\Service\Api;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Action\API\Webhook\ApiStateController;
use JobSearcher\Entity\Service\ServiceState;

/**
 * Provides logic for deciding the service state {@see ServiceState} such as:
 * - is it enabled?
 * - is it disabled
 */
class ApiStateService
{
    /**
     * Might happen that the name provided has some spacebars while the one in DB does not, so this will help reducing
     * the problem (*this is mostly the case for webhook based calls {@see ApiStateController} as some graphs
     * etc. might have data a bit malformed for GUI display purposes*)
     */
    private const MIN_PERCENT_SIMILARITY_NAME = 80;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Turn given service softly off - meaning it will not be allowed to be called from code side
     *
     * @throws Exception
     */
    public function disableApiService(string $searchedServiceName): void
    {
        // there won't be many so this solution is sane
        $allServices  = $this->entityManager->getRepository(ServiceState::class)->findAll();
        $foundService = null;
        foreach ($allServices as $service) {
            similar_text($service->getName(), $searchedServiceName, $similarityPercentage);
            if ($similarityPercentage < self::MIN_PERCENT_SIMILARITY_NAME) {
                continue;
            }

            if ($foundService) {
                $messageData = json_encode([
                    "message"     => "Cannot disable state for service: {$searchedServiceName}, as more than one matches were found",
                    "servicesIds" => [
                        $foundService->getId(),
                        $service->getId(),
                    ]
                ]);
                throw new Exception($messageData);
            }

            $foundService = $service;
        }

        if (!$foundService) {
            throw new Exception("Cannot disable state for service: {$searchedServiceName}, as no such is defined in DB");
        }

        $foundService->setActive(false);

        $this->entityManager->persist($foundService);
        $this->entityManager->flush();
    }

}