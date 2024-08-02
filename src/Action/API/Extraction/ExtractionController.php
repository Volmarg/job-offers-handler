<?php

namespace JobSearcher\Action\API\Extraction;

use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use JobSearcher\Response\Extraction\CountRunningExtractionsResponse;
use JobSearcher\Response\Extraction\GetExtractionsMinimalDataResponse;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\Extraction\Offer\ApiOfferExtractionService;
use JobSearcher\Service\Validation\ValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api/extraction/", name: "api.extraction.")]
class ExtractionController extends AbstractController
{
    public function __construct(
        private readonly JobOfferExtractionRepository $jobOfferExtractionRepository,
        private readonly ValidatorService             $validatorService,
        private readonly ApiOfferExtractionService    $apiOfferExtractionService
    ){}

    /**
     * Get the amount of running extractions in last 'x' hours
     *
     * @param int $hoursOffset
     *
     * @return JsonResponse
     */
    #[Route("count-running/{hoursOffset}", name: "count_running", methods: [Request::METHOD_GET])]
    public function countRunning(int $hoursOffset): JsonResponse
    {
        // setting some random value to deny starting search
        if (EnvReader::isDemo()) {
            $response = CountRunningExtractionsResponse::buildOkResponse();
            $response->setCount(99999999);

            return $response->toJsonResponse();
        }

        $minutesOffset = $hoursOffset * 60;
        $extractions   = $this->jobOfferExtractionRepository->findRunningLongerThan($minutesOffset);
        $count         = count($extractions);

        $response = CountRunningExtractionsResponse::buildOkResponse();
        $response->setCount($count);

        return $response->toJsonResponse();
    }

    /**
     * Get minimal data about the extractions.
     *
     * Keep in mind that it should really provide only minimal data as else it might slow the page loading etc.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route("get-minimal-data", name: "get_minimal_data", methods: [Request::METHOD_POST])]
    public function getMinimalData(Request $request): JsonResponse
    {
        $json = $request->getContent();
        if (!$this->validatorService->validateJson($json)) {
            return (GetExtractionsMinimalDataResponse::buildInvalidJsonResponse())->toJsonResponse();
        }
        $response = GetExtractionsMinimalDataResponse::buildOkResponse();

        $dataArray         = json_decode($json, true);
        $userExtractionIds = $dataArray['extractionIds'] ?? [];
        $clientSearchIds   = $dataArray['clientSearchIds'] ?? [];
        if (empty($userExtractionIds) && empty($clientSearchIds)) {
            return $response->toJsonResponse();
        }

        $this->apiOfferExtractionService->setMinimalData($userExtractionIds, $clientSearchIds, $response);

        return $response->toJsonResponse();
    }
}