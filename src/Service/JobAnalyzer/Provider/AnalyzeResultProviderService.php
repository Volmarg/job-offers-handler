<?php

namespace JobSearcher\Service\JobAnalyzer\Provider;

use Exception;
use JobSearcher\DTO\JobService\JobOfferWithProcessedResultDto;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Service\JobAnalyzer\JobSearchResultAnalyzerService;

/**
 * Handles providing analyse of the {@see SearchResultDto}
 */
class AnalyzeResultProviderService
{
    /**
     * Will take an array of {@see SearchResultDto}, analyze each one of the job offer search result by using provided
     * {@see JobOfferFilterDto}, and creates {@see JobOfferWithProcessedResultDto} for single analyze.
     *
     * As result an array of the {@see JobOfferWithProcessedResultDto} will be returned for all analyzed SearchResults
     *
     * @param SearchResultDto[] $searchResults
     *
     * @return JobOfferWithProcessedResultDto[]
     * @throws Exception
     */
    public function analyzeJobOfferSearchResults(array $searchResults, JobOfferFilterDto $jobOfferFilterDto): array
    {
        $jobOffersWithProcessedResults = [];
        foreach($searchResults as $searchResult){
            $jobOfferWithProcessedResultDto  = $this->analyzeSingleJobOfferSearchResult($searchResult, $jobOfferFilterDto);
            $jobOffersWithProcessedResults[] = $jobOfferWithProcessedResultDto;
        }

        return $jobOffersWithProcessedResults;
    }


    /**
     * Will analyze single {@see SearchResultDto} by using the {@see JobOfferFilterDto}
     * Returns {@see JobOfferWithProcessedResultDto}
     *
     * @param SearchResultDto   $searchResultDto
     * @param JobOfferFilterDto $jobOfferFilterDto
     *
     * @return JobOfferWithProcessedResultDto
     * @throws Exception
     */
    public function analyzeSingleJobOfferSearchResult(SearchResultDto $searchResultDto, JobOfferFilterDto $jobOfferFilterDto): JobOfferWithProcessedResultDto
    {
        $analyzer       = new JobSearchResultAnalyzerService($searchResultDto, $jobOfferFilterDto);
        $analyzedResult = $analyzer->analyzeSearchResult();

        $jobOfferWithProcessedResultDto = new JobOfferWithProcessedResultDto();
        $jobOfferWithProcessedResultDto->setSearchResultAnalyzedDto($analyzedResult);
        $jobOfferWithProcessedResultDto->setSearchResultDto($searchResultDto);

        return $jobOfferWithProcessedResultDto;
    }
}