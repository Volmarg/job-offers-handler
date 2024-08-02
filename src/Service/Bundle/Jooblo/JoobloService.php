<?php

namespace JobSearcher\Service\Bundle\Jooblo;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JoobloBridge\Request\Job\IsOfferUsedRequest;
use JoobloBridge\Request\System\IsSystemDisabledRequest;
use JoobloBridge\Service\BridgeService;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Connects to jooblo / voltigo backend
 */
class JoobloService
{
    /**
     * @param BridgeService   $bridgeService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly BridgeService $bridgeService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function isOfferUsed(JobSearchResult $jobOffer): bool
    {
        try {
            $request = new IsOfferUsedRequest();
            $request->setOfferId($jobOffer->getId());
            $response = $this->bridgeService->isOfferUsed($request);

            if (!$response->isSuccess()) {
                // could not check if it's used so assuming it is - for safety
                $this->logger->critical("Called jooblo/voltigo - checking if job id is used, got NON successful response", [
                    "info" => "Will assume that it's used just for safety, nothing bad happens with this",
                    "response" => [
                        "msg" => $response->getMessage(),
                        "code" => $response->getCode(),
                    ]
                ]);
                return true;
            }

            return !$response->isNotFound();
        } catch (Exception|TypeError $e) {

            $this->logger->critical("Called jooblo/voltigo - checking if job id is used, got exception", [
                "info" => "Will assume that it's used just for safety, nothing bad happens with this",
                "exception" => [
                    "msg" => $e->getMessage(),
                    "code" => $e->getCode(),
                ]
            ]);

            // Assuming it is used - for safety
            return true;
        }
    }

    /**
     * Check if the target system is disabled
     *
     * @return bool
     *
     * @throws GuzzleException
     */
    public function isSystemDisabled(): bool
    {
        try {
            $request  = new IsSystemDisabledRequest();
            $response = $this->bridgeService->isSystemDisabled($request);
            if (!$response->isSuccess()) {
                throw new Exception("Called jooblo/voltigo - checking if system is disabled, got NON-successful response. Msg: " . $response->getMessage());
            }

            return $response->isDisabled();
        } catch (Exception|TypeError $e) {
            $this->logger->critical("Called jooblo/voltigo - checking if jooblo/voltigo is disabled, got exception", [
                "exception" => [
                    "msg"  => $e->getMessage(),
                    "code" => $e->getCode(),
                ],
            ]);
            throw $e;
        }
    }
}