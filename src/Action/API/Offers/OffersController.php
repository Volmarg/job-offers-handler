<?php

namespace JobSearcher\Action\API\Offers;

use Exception;
use JobSearcher\DTO\Api\Transport\JobOfferAnalyseResultDto;
use JobSearcher\DTO\Api\Transport\Offer\ExcludedOfferData;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Response\Offer\GetFullDescriptionResponse;
use JobSearcher\Response\Offer\GetJobOffersResponse;
use JobSearcher\Response\Offer\GetSingleOfferResponse;
use JobSearcher\Service\Api\Provider\JobOffer\JobOffersProviderService;
use JobSearcher\Service\Filters\FilterValuesService;
use JobSearcher\Service\JobAnalyzer\JobSearchResultAnalyzerInterface;
use JobSearcher\Service\JobAnalyzer\JobSearchResultAnalyzerService;
use JobSearcher\Service\Serialization\ObjectSerializerService;
use JobSearcher\Service\Validation\ValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api", name: "api.")]
class OffersController extends AbstractController
{
    private const KEY_FILTER               = "filter";
    private const KEY_EXCLUDED_OFFERS_DATA = "excludedOffersData";
    private const KEY_USER_EXTRACTION_IDS  = "userExtractionIds";

    public function __construct(
        private readonly JobOffersProviderService $jobOffersProviderService,
        private readonly ObjectSerializerService  $objectSerializerService,
        private readonly ValidatorService         $validatorService,
        private readonly FilterValuesService      $filterValuesService
    )
    {}

    /**
     * Will return the job offers for provided extraction and filter rules
     *
     * @throws Exception
     */
    #[Route("/offers/get/{id}", name: "offers.get", methods: [Request::METHOD_POST])]
    public function getOffers(JobOfferExtraction $jobOfferExtraction, Request $request): JsonResponse
    {
        $json = $request->getContent();
        if (!$this->validatorService->validateJson($json)) {
            return (GetJobOffersResponse::buildInvalidJsonResponse())->toJsonResponse();
        }

        $dataArray               = json_decode($json, true);
        $filterArray             = $dataArray[self::KEY_FILTER] ?? null;
        $excludedOffersDataArray = $dataArray[self::KEY_EXCLUDED_OFFERS_DATA] ?? [];
        $userExtractionIds       = $dataArray[self::KEY_USER_EXTRACTION_IDS] ?? [];
        if (empty($filterArray)) {
            throw new BadRequestException("Filter is missing in the request");
        }

        $excludedOffersDtos = array_map(
            fn($dataSet) => $this->objectSerializerService->fromJson(json_encode($dataSet), ExcludedOfferData::class),
            $excludedOffersDataArray,
        );

        /** @var JobOfferFilterDto $filter */
        $filterJson = json_encode($filterArray);
        $filter     = $this->objectSerializerService->fromJson($filterJson, JobOfferFilterDto::class);

        $offers   = $this->jobOffersProviderService->getResultsAsArray($filter, [$jobOfferExtraction], $excludedOffersDtos, $userExtractionIds);
        $response = GetJobOffersResponse::buildOkResponse();
        $response->setOffersArray($offers);

        $jobOfferExtraction->setFilterOffersWithoutCompanyBranch(true);
        $response->setAllFoundOffersCount($jobOfferExtraction->getJobSearchResults()->count());
        $response->setReturnedOffersCount(count($offers));

        $offersIds = array_map(
            fn(array $offerData) => $offerData['identifier'],
            $offers,
        );

        $this->filterValuesService->setExtraction($jobOfferExtraction);
        $this->filterValuesService->setOfferIds($offersIds);
        $filterValues = $this->filterValuesService->provide();

        $response->setFilterValues($filterValues);

        return $response->toJsonResponse();
    }

    /**
     * Will return full job offer description
     *
     * @param JobSearchResult $jobSearchResult
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route("/offers/get/full-description/{id}", name: "offers.get.full_description", methods: [Request::METHOD_POST])]
    public function getOfferFullDescription(JobSearchResult $jobSearchResult, Request $request): JsonResponse
    {
        if (!$this->validatorService->validateJson($request->getContent())) {
            return (GetJobOffersResponse::buildInvalidJsonResponse())->toJsonResponse();
        }

        $requestData = json_decode($request->getContent(), true);
        $filterArray = $requestData[self::KEY_FILTER] ?? null;
        if (empty($filterArray)) {
            throw new BadRequestException("Filter is missing in the request");
        }

        /** @var JobOfferFilterDto $filter */
        $filterJson = json_encode($filterArray);
        $filter     = $this->objectSerializerService->fromJson($filterJson, JobOfferFilterDto::class);

        $allKeywords = [
            ...$filter->getIncludedKeywords(),
            ...$filter->getMandatoryIncludedKeywords(),
        ];

        $wrappedKeywords = JobSearchResultAnalyzerService::doWrapKeywordsInDescription(
            JobSearchResultAnalyzerInterface::KEYWORDS_TYPE_INCLUDED,
            $allKeywords,
            $jobSearchResult->getJobDescription()
        );

        $response = GetFullDescriptionResponse::buildOkResponse();
        $response->setDescription($wrappedKeywords->getStringAfterApplyingKeywords());

        return $response->toJsonResponse();
    }

    /**
     * Will return single analysed offer for provided offer id and set of filter rules (used to find that offer)
     *
     * @param JobSearchResult $jobSearchResult
     * @param Request         $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/offers/get-single-analysed-offer/{id}", name: "offers.get.single_analysed", methods: [Request::METHOD_POST])]
    public function getSingleAnalysed(JobSearchResult $jobSearchResult, Request $request): JsonResponse
    {
        if (!$this->validatorService->validateJson($request->getContent())) {
            return (GetJobOffersResponse::buildInvalidJsonResponse())->toJsonResponse();
        }

        $requestData = json_decode($request->getContent(), true);
        $filterArray = $requestData[self::KEY_FILTER] ?? null;
        if (empty($filterArray)) {
            throw new BadRequestException("Filter is missing in the request");
        }

        /** @var JobOfferFilterDto $filter */
        $filterJson = json_encode($filterArray);
        $filter     = $this->objectSerializerService->fromJson($filterJson, JobOfferFilterDto::class);

        $processedOffer = $this->jobOffersProviderService->getSingleProcessedWithOffer($jobSearchResult, $filter);
        $analyseResult  = JobOfferAnalyseResultDto::buildFromProcessedResult($processedOffer);
        $offerDataArray = $this->objectSerializerService->toArray($analyseResult);

        $response = GetSingleOfferResponse::buildOkResponse();
        $response->setOfferData($offerDataArray);

        return $response->toJsonResponse();
    }
}