<?php

namespace JobSearcher\Service\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Exception\Extraction\TerminateProcessException;
use JobSearcher\Service\CompanyData\CompanyDataService;
use JobSearcher\Service\Keywords\KeywordsService;
use JobSearcher\Service\Location\LocationService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Handles providing data for the {@see JobSearchResult}, such as:
 * - keywords: {@see KeywordsService}
 * - location: {@see LocationService}
 * - etc.
 *
 * Basically saying, whatever the {@see JobSearchResult} needs to be provided with it's handled in here,
 * so one entity of {@see JobSearchResult} will go through all the services it has to and will set the received data
 */
class JobSearchDataProviderService
{
    /**
     * @param KeywordsService          $keywordsService
     * @param LocationService          $locationService
     * @param CompanyDataService       $companyDataService
     * @param EntityManagerInterface   $entityManager
     */
    public function __construct(
        private readonly KeywordsService          $keywordsService,
        private readonly LocationService          $locationService,
        private readonly CompanyDataService       $companyDataService,
        private readonly EntityManagerInterface   $entityManager
    ){}

    /**
     * Provides all the necessary data for single job search result (offer)
     *
     * @param JobSearchResult    $jobSearchResult
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @return void
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws TerminateProcessException
     */
    public function provideForSingleOffer(JobSearchResult $jobSearchResult, JobOfferExtraction $jobOfferExtraction): void
    {
        $this->keywordsService->getForOffer($jobSearchResult);

        if ($this->shouldProvideDataForOffer($jobSearchResult)) {
            $this->companyDataService->getForCompanyBranch(
                $jobSearchResult->getCompanyBranch(),
                $jobOfferExtraction->getCountry() ?? $jobSearchResult->getOfferLanguageIsoCodeThreeDigit()
            );
        }

        $this->provideLocationsData($jobSearchResult);
    }

    /**
     * Will check if data for offer should be provided
     *
     * @param JobSearchResult $jobOffer
     *
     * @return bool
     */
    private function shouldProvideDataForOffer(JobSearchResult $jobOffer): bool
    {
        return(
            (
                    empty($jobOffer->getCompany()->getWebsite())
                ||  empty($jobOffer->getCompany()->getLinkedinUrl())
                ||  (
                            empty($jobOffer->getCompany()->getJobApplicationEmails())
                        &&  empty($jobOffer->getEmail())
                    )
            )
            &&  !empty($jobOffer->getCompanyBranch())
        );
    }

    /**
     * Will provide data for search result locations
     *
     * @param JobSearchResult $jobSearchResult
     *
     * @return void
     */
    private function provideLocationsData(JobSearchResult $jobSearchResult): void
    {
        /**
         * Reference is used because location might already exist and is returned in that case,
         * so the existing one will be taken - that's added to prevent having duplicated location based entries
         */
        $isAnyLocationUpdated = false;
        foreach ($jobSearchResult->getLocations() as &$location) {
            $updatedLocation = $this->locationService->getDataForLocation($location);
            if (!empty($updatedLocation)) {
                $isAnyLocationUpdated = true;
                $location             = $updatedLocation;

                $this->entityManager->persist($location);
            }
        }

        if ($isAnyLocationUpdated) {
            $this->entityManager->flush();
        }
    }

}