<?php

namespace JobSearcher\Service\JobSearch\Decider;

use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Service\JobSearch\Decider\Company\DeniedCompanyHandler;
use Psr\Log\LoggerInterface;

/**
 * Contains logic for deciding if the built offer should be saved or rejected.
 * The entire rejected offer is lost (not stored anywhere)
 */
class OfferSavingDecider
{
    public function __construct(
        private readonly LoggerInterface $offerExtractionLogger
    ) {
    }

    /**
     * Goes over bunch of rules and decides if offer should be saved or not
     *
     * @param SearchResultDto       $jobOfferSearchResult
     * @param JobSearchParameterBag $searchParams
     *
     * @return bool
     */
    public function canSave(SearchResultDto $jobOfferSearchResult, JobSearchParameterBag $searchParams): bool
    {
        if (!$this->hasJobTitle($jobOfferSearchResult)) {
            return false;
        }

        if (!$this->hasDescription($jobOfferSearchResult)) {
            return false;
        }

        if (!$this->hasLocation($jobOfferSearchResult)) {
            return false;
        }

        if (!$this->isSearchParamsCountrySet($searchParams)) {
            return true;
        }

        if (!$this->hasCompanyName($jobOfferSearchResult)) {
            return false;
        }

        if ($this->hasDeniedCompanyName($jobOfferSearchResult, $searchParams)) {
            return false;
        }

        return true;
    }

    /**
     * @param SearchResultDto $jobOfferSearchResult
     *
     * @return bool
     */
    private function hasJobTitle(SearchResultDto $jobOfferSearchResult): bool
    {
        $hasTitle = !empty($jobOfferSearchResult->getJobDetailDto()->getJobTitle());
        if (!$hasTitle) {
            $this->offerExtractionLogger->critical("Offer saving is denied", [
                "reason" => "Title not set",
                "url"    => $jobOfferSearchResult->getJobOfferUrl(),
            ]);
        }

        return $hasTitle;
    }

    /**
     * @param SearchResultDto $jobOfferSearchResult
     *
     * @return bool
     */
    private function hasDescription(SearchResultDto $jobOfferSearchResult): bool
    {
        $hasDescription = !empty($jobOfferSearchResult->getJobDetailDto()->getJobDescription());
        if (!$hasDescription) {
            $this->offerExtractionLogger->critical("Offer saving is denied", [
                "reason" => "Description not set",
                "url"    => $jobOfferSearchResult->getJobOfferUrl(),
            ]);
        }

        return $hasDescription;
    }

    /**
     * @param SearchResultDto $jobOfferSearchResult
     *
     * @return bool
     */
    private function hasLocation(SearchResultDto $jobOfferSearchResult): bool
    {
        foreach ($jobOfferSearchResult->getCompanyDetailDto()->getCompanyLocations() as $location) {
            if (!empty(trim($location))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param SearchResultDto $jobOfferSearchResult
     *
     * @return bool
     */
    private function hasCompanyName(SearchResultDto $jobOfferSearchResult): bool
    {
        return !empty($jobOfferSearchResult->getCompanyDetailDto()->getCompanyName());
    }

    /**
     * @param JobSearchParameterBag $searchParams
     *
     * @return bool
     */
    private function isSearchParamsCountrySet(JobSearchParameterBag $searchParams): bool
    {
        $isCountrySet = !empty($searchParams->getCountry());
        if (!$isCountrySet) {
            $msg = "Got extraction without country, cannot handle the country based checks then. Allowing to save = true";
            $this->offerExtractionLogger->warning($msg);
        }

        return $isCountrySet;
    }

    /**
     * @param SearchResultDto       $jobOfferSearchResult
     * @param JobSearchParameterBag $searchParams
     *
     * @return bool
     */
    private function hasDeniedCompanyName(SearchResultDto $jobOfferSearchResult, JobSearchParameterBag $searchParams): bool
    {
        $isDenied = DeniedCompanyHandler::isNameDenied(
            $searchParams->getCountry(),
            $jobOfferSearchResult->getCompanyDetailDto()->getCompanyName()
        );

        if ($isDenied) {
            $this->offerExtractionLogger->warning("Offer saving denied, company name is denied", [
                "url"     => $jobOfferSearchResult->getJobOfferUrl(),
                "company" => $jobOfferSearchResult->getCompanyDetailDto()->getCompanyName(),
                "country" => $searchParams->getCountry()
            ]);
        }

        return $isDenied;
    }
}
