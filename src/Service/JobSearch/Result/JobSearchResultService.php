<?php

namespace JobSearcher\Service\JobSearch\Result;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use JobSearcher\DTO\Api\Transport\Offer\ExcludedOfferData;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\General\GeneralSearchResult;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Company\CompanyRawSqlRepository;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Service\CompanyData\CompanyBranchService;
use JobSearcher\Service\CompanyData\CompanyService;
use JobSearcher\Service\Email\EmailService;
use JobSearcher\Service\JobAnalyzer\JobSearchResultAnalyzerService;
use JobSearcher\Service\JobSearch\Result\Handlers\SearchResultLocationHandler;

/**
 * Common logic for handling job search result entities
 *
 * Important notes:
 *  1. Sometimes it happens that the location "1234 Xyz" is being visible on the offer, while the minimap (on front)
 *     shows different address. This has been investigated, and it's just companies issues of not updating maps,
 *     because data fetched from offers is correct.
 */
class JobSearchResultService
{

    /**
     * @var JobSearchResultRepository $jobSearchResultRepository
     */
    private JobSearchResultRepository $jobSearchResultRepository;

    /**
     * @param JobSearchResultRepository   $jobSearchResultRepository
     * @param EmailService                $emailService
     * @param CompanyService              $companyService
     * @param CompanyRawSqlRepository     $companyRawSqlRepository
     * @param OffersFilterService         $filterService
     * @param SearchResultLocationHandler $searchResultLocationHandler
     */
    public function __construct(
        JobSearchResultRepository                    $jobSearchResultRepository,
        private readonly EmailService                $emailService,
        private readonly CompanyService              $companyService,
        private readonly CompanyRawSqlRepository     $companyRawSqlRepository,
        private readonly OffersFilterService         $filterService,
        private readonly SearchResultLocationHandler $searchResultLocationHandler
    )
    {
        $this->jobSearchResultRepository = $jobSearchResultRepository;
    }

    /**
     * Will return entity usable for given job search configuration
     *
     * @param string $configurationName
     *
     * @return JobSearchResult
     */
    public function getEntityForConfigurationName(string $configurationName): JobSearchResult
    {
        $entityNamespace = $this->getEntityNamespaceForConfigurationName($configurationName);
        $entity          = new $entityNamespace();

        return $entity;
    }

    /**
     * Will return entity usable for given job search configuration
     *
     * @param string $configurationName
     * @return string
     */
    public function getEntityNamespaceForConfigurationName(string $configurationName): string
    {
        if( !array_key_exists($configurationName, JobSearchResult::MAPPING_JOB_CONFIGURATION_NAME_TO_ENTITY_FQN) ){
            return GeneralSearchResult::class;
        }

        $entityNamespace = JobSearchResult::MAPPING_JOB_CONFIGURATION_NAME_TO_ENTITY_FQN[$configurationName];
        return $entityNamespace;
    }

    /**
     * Returns job offers by the {@see JobOfferFilterDto}
     *
     * @param JobOfferFilterDto    $jobOfferFilterDto
     * @param JobOfferExtraction[] $jobOfferExtractions
     * @param ExcludedOfferData[]  $excludedOffersDtos
     * @param int[] $userExtractionIds
     *
     * @return JobSearchResult[]
     * @throws Exception
     */
    public function findByFilterDto(JobOfferFilterDto $jobOfferFilterDto, array $jobOfferExtractions, array $excludedOffersDtos, array $userExtractionIds): array
    {
        $currentlyHandledExtraction = (count($jobOfferExtractions) === 1 ? $jobOfferExtractions[array_key_first($jobOfferExtractions)] : null);

        $jobOffers = $this->jobSearchResultRepository->findByFilterDto($jobOfferFilterDto, $jobOfferExtractions);
        $jobOffers = $this->treatOfferDescriptionLanguageAsHumanLanguage($jobOffers, $jobOfferFilterDto);
        $jobOffers = $this->filterService->applyFilters($jobOffers, $jobOfferFilterDto, $excludedOffersDtos, $userExtractionIds, $currentlyHandledExtraction);

        // reindex keys
        $filteredOffers = array_values($jobOffers);

        return $filteredOffers;
    }

    /**
     * Will build any child extending from the parent, it uses the {@see SearchResultDto}
     * which was used for creating search results.
     *
     * It could be replaced by {@see JobSearchResult} but was decided that it won't be any big mess
     * to keep these both, also the Dto structure is nicer to navigate, yet the entity must be added
     * to represent the DB entries
     *
     * >WARNING< Be careful with adding more logic in here, scroll over the code and read about the known
     *           issues with deadlock
     *
     * @param SearchResultDto $searchResultDto
     * @param string          $configuration
     *
     * @return JobSearchResult
     * @throws NonUniqueResultException
     * @throws \Doctrine\DBAL\Exception
     */
    public function buildJobSearchResultFromSearchResultDto(SearchResultDto $searchResultDto, string $configuration): JobSearchResult
    {
        $offerEntity = $this->getEntityForConfigurationName($configuration);

        $offerEntity->setMentionedHumanLanguages($searchResultDto->getMentionedHumanLanguages());
        $offerEntity->setJobTitle($searchResultDto->getJobDetailDto()->getJobTitle());
        $offerEntity->setJobDescription($searchResultDto->getJobDetailDto()->getJobDescription());
        $offerEntity->setJobOfferUrl($searchResultDto->getJobOfferUrl());
        $offerEntity->setJobOfferHost($searchResultDto->getJobOfferHost());
        $offerEntity->setSalaryMin($searchResultDto->getSalaryDto()->getSalaryMin());
        $offerEntity->setSalaryMax($searchResultDto->getSalaryDto()->getSalaryMax());
        $offerEntity->setSalaryAverage($searchResultDto->getSalaryDto()->getSalaryAverage());
        $offerEntity->setRemoteJobMentioned($searchResultDto->isRemoteJobMentioned());
        $offerEntity->setJobPostedDateTime($searchResultDto->getJobPostedDateTime());

        $offerEntity = $this->searchResultLocationHandler->setEntityLocationsFromSearch($offerEntity, $searchResultDto);

        // handle company data
        if( !empty($searchResultDto->getCompanyDetailDto()->getCompanyName()) ){
            $companyEntity = $this->companyService->findOneCompany(
                $searchResultDto->getCompanyDetailDto()->getCompanyName()
            ) ?? new Company();

            if (!$companyEntity->getId()) {
                $companyEntity->setName($searchResultDto->getCompanyDetailDto()->getCompanyName());
                $companyEntity->setLastTimeRelatedToOfferAsToday();
            } else {
                /**
                 * This was added due to issues with deadlock, it's really unknown how is it possible that this single
                 * field change was causing deadlock, but after doing it the "raw-sql" way, it solved the problem,
                 */
                $companyEntity = $this->companyRawSqlRepository->setLastTimeRelatedToOfferAsToday($companyEntity->getId());
            }

            $offerEntity->setCompany($companyEntity);

            // it's allowed to have job offer without location, branch is more important, as it stores E-Mails etc.
            if (empty($offerEntity->getLocations())) {

                $companyBranch = CompanyBranchService::buildFromSearchResult($searchResultDto);
                $offerEntity->setCompanyBranch($companyBranch);
            }else{

                foreach ($offerEntity->getLocations() as $location) {
                    $companyBranch = $companyEntity->getBranchForLocation($location->getName());
                    if (empty($companyBranch)) {
                        $companyBranch = CompanyBranchService::buildFromSearchResult($searchResultDto, $location);
                        $companyEntity->addCompanyBranch($companyBranch);
                    }

                    $offerEntity->setCompanyBranch($companyBranch);
                }
            }
        }

        if (!empty($searchResultDto->getContactDetailDto()->getEmail())) {
            $this->emailService->buildAndBindEmailToJobOffer($offerEntity, $searchResultDto->getContactDetailDto()->getEmail(), true);
        }

        return $offerEntity;
    }

    /**
     * If the filter dto rule for treating the offer description as human language then:
     * - offer description language is added to the pool of mentioned human languages
     *
     * It's made like this because sometimes the searched language can be english but the offer already IS in english
     * so, it's natural it would not mention "english" anywhere in text.
     *
     * @param array             $jobOffers
     * @param JobOfferFilterDto $jobOfferFilterDto
     *
     * @return JobSearchResult[]
     */
    private function treatOfferDescriptionLanguageAsHumanLanguage(array $jobOffers, JobOfferFilterDto $jobOfferFilterDto): array
    {
        if ($jobOfferFilterDto->isTreatOfferDescriptionLanguageAsHumanLanguage()) {
            foreach ($jobOffers as $jobOffer) {
                /** This is not used in DB calls, only adding it here as language, rejection is based on {@see JobSearchResultAnalyzerService} */
                $mentionedHumanLanguages = array_unique([
                    ...$jobOffer->getMentionedHumanLanguages() ?? [],
                    $jobOffer->getOfferLanguage()
                ]);
                $jobOffer->setMentionedHumanLanguages($mentionedHumanLanguages);
            }
        }

        return $jobOffers;
    }
}