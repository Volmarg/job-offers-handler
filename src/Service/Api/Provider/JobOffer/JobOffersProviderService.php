<?php

namespace JobSearcher\Service\Api\Provider\JobOffer;

use Exception;
use JobSearcher\Command\Cleanup\EmailCleanupCommand;
use JobSearcher\DTO\Api\Transport\JobOfferAnalyseResultDto;
use JobSearcher\DTO\Api\Transport\Offer\ExcludedOfferData;
use JobSearcher\DTO\JobService\JobOfferWithProcessedResultDto;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Email\EmailRepository;
use JobSearcher\Service\Email\EmailService;
use JobSearcher\Service\JobAnalyzer\Provider\AnalyzeResultProviderService;
use JobSearcher\Service\JobProcessor\JobOfferProcessorService;
use JobSearcher\Service\JobSearch\Result\JobSearchResultService;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles logic for providing data  for any external calls
 */
class JobOffersProviderService
{

    /**
     * @var JobSearchResultService $jobSearchResultService
     */
    private JobSearchResultService $jobSearchResultService;

    /**
     * @var SerializerInterface $serializer
     */
    private SerializerInterface $serializer;

    /**
     * @param JobSearchResultService       $jobSearchResultService
     * @param AnalyzeResultProviderService $analyzeResultProviderService
     * @param SerializerInterface          $serializer
     * @param EmailService                 $emailService
     * @param JobOfferProcessorService     $jobOfferProcessorService
     * @param EmailRepository              $emailRepository
     */
    public function __construct(
        JobSearchResultService                        $jobSearchResultService,
        private readonly AnalyzeResultProviderService $analyzeResultProviderService,
        SerializerInterface                           $serializer,
        private readonly EmailService                 $emailService,
        private readonly JobOfferProcessorService     $jobOfferProcessorService,
        private readonly EmailRepository              $emailRepository
    )
    {
        $this->jobSearchResultService = $jobSearchResultService;
        $this->serializer             = $serializer;
    }

    /**
     * That's the main method to be called in any tool in which it's implemented
     * Will find for job offers matching given filter configuration and returns offer data:
     * - original offer data,
     * - analysis result data,
     *
     * Fetches data from DATABASE, not from external services
     *
     * @param JobOfferFilterDto $jobOfferFilterDto
     * @param JobOfferExtraction[] $jobOfferExtractions
     * @param ExcludedOfferData[] $excludedOffersDtos
     * @param int[] $userExtractionIds
     *
     * @return JobOfferWithProcessedResultDto[]
     * @throws Exception
     */
    private function getResults(JobOfferFilterDto $jobOfferFilterDto, array $jobOfferExtractions, array $excludedOffersDtos, array $userExtractionIds): array
    {
        $searchResultsEntities = $this->jobSearchResultService->findByFilterDto($jobOfferFilterDto, $jobOfferExtractions, $excludedOffersDtos, $userExtractionIds);
        $searchResultsDtos     = [];

        foreach ($searchResultsEntities as $jobSearchResult) {
            $jobApplicationEmail = $this->emailService->getEmailUsedForJobApplication($jobSearchResult);
            $searchResultsDtos[] = SearchResultDto::fromJobSearchResultEntity($jobSearchResult, $jobApplicationEmail, true);
        }

        $this->jobOfferProcessorService->setTagsWithColors($jobOfferFilterDto->getHighlightedKeywords());

        $companyEmails          = [];
        $processedSearchResults = $this->jobOfferProcessorService->processSearchResults($searchResultsDtos);
        $analyzeResult          = $this->analyzeResultProviderService->analyzeJobOfferSearchResults($processedSearchResults, $jobOfferFilterDto);
        foreach ($analyzeResult as $singleAnalyze) {
            $companyEmails = $this->setMissingApplicationEmail($singleAnalyze, $companyEmails);
            foreach ($searchResultsEntities as $searchResultsEntity) {
                if ($searchResultsEntity->getId() == $singleAnalyze->getSearchResultDto()->getIdentifier()) {
                    $singleAnalyze->setSavedOfferEntity($searchResultsEntity);
                    break;
                }
            }
        }

        return $analyzeResult;
    }

    /**
     * Works just like {@see JobOffersProviderService::getResults()} but returns jsons instead of objects
     *
     * @param JobOfferFilterDto $jobOfferFilterDto
     * @param JobOfferExtraction[] $jobOfferExtractions
     * @param ExcludedOfferData[] $excludedOffersDtos
     * @param int[] $userExtractionIds
     *
     * @return string[]
     * @throws Exception
     */
    public function getResultsAsArray(JobOfferFilterDto $jobOfferFilterDto, array $jobOfferExtractions, array $excludedOffersDtos, array $userExtractionIds): array
    {
        $analyzeResultsWithProcessedResult = $this->getResults($jobOfferFilterDto, $jobOfferExtractions, $excludedOffersDtos, $userExtractionIds);

        $analyseResult = array_map(
            fn(JobOfferWithProcessedResultDto $result) => JobOfferAnalyseResultDto::buildFromProcessedResult($result),
            $analyzeResultsWithProcessedResult
        );

        $dataArray = array_map(
            fn(JobOfferAnalyseResultDto $result) => json_decode(
                $this->serializer->serialize($result, "json"),
                true
            ),
            $analyseResult
        );

        return $dataArray;
    }

    /**
     * Returns {@see JobOfferWithProcessedResultDto} for provided offer and filter data
     *
     * @param JobSearchResult   $jobOffer
     * @param JobOfferFilterDto $filterDto
     *
     * @return JobOfferWithProcessedResultDto
     * @throws Exception
     */
    public function getSingleProcessedWithOffer(JobSearchResult $jobOffer, JobOfferFilterDto $filterDto): JobOfferWithProcessedResultDto
    {
        $jobApplicationEmail = $this->emailService->getEmailUsedForJobApplication($jobOffer);
        $searchResultDto     = SearchResultDto::fromJobSearchResultEntity($jobOffer, $jobApplicationEmail, true);

        $this->jobOfferProcessorService->processSearchResult($searchResultDto);

        $analyzeResult         = $this->analyzeResultProviderService->analyzeSingleJobOfferSearchResult($searchResultDto, $filterDto);
        $analyzeResult->setSavedOfferEntity($jobOffer);

        return $analyzeResult;
    }

    /**
     * Case 1:
     * This is kind of a hack. In perfect world situation if one company has 2 offers during same search then naturally
     * the same company data is going to be used (email in this case), but sadly it's not the perfect world.
     *
     * There are issues with deadlocks when multiple users use the platforms, therefore a lot of uniq checks has been
     * disabled, so in one case E-Mail could be extracted and set, but for other E-Mail won't be set:
     * - could be that page banned,
     * - search result changed,
     * - etc.
     *
     * So in here if same company is assigned few times to offers and one of the offers has the E-Mail set then,
     * it's very safe to assume that it's still the same company.
     *
     * Case 2:
     * Pretty much same as before but this time going over DB and doing search for:
     * - same company name,
     * - same website
     *
     * > Warning <
     *
     * All of this here is a hack really, there is this {@see EmailCleanupCommand} etc. which handles the merge of duplicates
     * etc. but this happens only at night, yet user might want to send E-Mails now. So this kinda helps in this situation.
     *
     * @param JobOfferWithProcessedResultDto $singleAnalyze
     * @param array                          $companyEmails
     *
     * @return array
     */
    private function setMissingApplicationEmail(JobOfferWithProcessedResultDto $singleAnalyze, array $companyEmails): array
    {
        $companyName = $singleAnalyze->getSearchResultDto()->getCompanyDetailDto()->getCompanyName();
        $websiteUrl  = $singleAnalyze->getSearchResultDto()->getCompanyDetailDto()->getWebsiteUrl();
        $email       = $singleAnalyze->getSearchResultDto()->getContactDetailDto()->getEmail();
        if (!empty($email) && !empty($companyName)) {
            $companyEmails[$companyName] = $email;
        }

        if (!empty($companyName) && empty($email) && array_key_exists($companyName, $companyEmails)) {
            $emailForCompany = $companyEmails[$companyName];
            $singleAnalyze->getSearchResultDto()->getContactDetailDto()->setEmail($emailForCompany);
        }

        if (empty($singleAnalyze->getSearchResultDto()->getContactDetailDto()->getEmail()) && !empty($websiteUrl)) {
            $emailAddress = $this->emailRepository->findAddressByCompany($companyName, $websiteUrl);
            if (!empty($emailAddress)) {
                $companyEmails[$companyName] = $emailAddress;
                $singleAnalyze->getSearchResultDto()->getContactDetailDto()->setEmail($emailAddress);
            }
        }

        return $companyEmails;
    }

}