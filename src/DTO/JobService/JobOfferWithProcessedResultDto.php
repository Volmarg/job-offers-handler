<?php

namespace JobSearcher\DTO\JobService;

use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\DTO\JobService\SearchResultAnalyze\SearchResultAnalyzedDto;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;

/**
 * Contain all the data about:
 * - original job offer that was being handled,
 * - job offer data after processing and analyzing it,
 */
class JobOfferWithProcessedResultDto
{

    /**
     * @var SearchResultAnalyzedDto $searchResultAnalyzedDto
     */
    private SearchResultAnalyzedDto $searchResultAnalyzedDto;

    /**
     * @var SearchResultDto $searchResultDto
     */
    private SearchResultDto $searchResultDto;

    /**
     * Represents the entity that was created & saved for offer found on internet
     *
     * @var JobSearchResult|null $savedOfferEntity
     */
    private ?JobSearchResult $savedOfferEntity = null;

    /**
     * @return SearchResultAnalyzedDto
     */
    public function getSearchResultAnalyzedDto(): SearchResultAnalyzedDto
    {
        return $this->searchResultAnalyzedDto;
    }

    /**
     * @param SearchResultAnalyzedDto $searchResultAnalyzedDto
     */
    public function setSearchResultAnalyzedDto(SearchResultAnalyzedDto $searchResultAnalyzedDto): void
    {
        $this->searchResultAnalyzedDto = $searchResultAnalyzedDto;
    }

    /**
     * @return SearchResultDto
     */
    public function getSearchResultDto(): SearchResultDto
    {
        return $this->searchResultDto;
    }

    /**
     * @param SearchResultDto $searchResultDto
     */
    public function setSearchResultDto(SearchResultDto $searchResultDto): void
    {
        $this->searchResultDto = $searchResultDto;
    }

    /**
     * @return JobSearchResult|null
     */
    public function getSavedOfferEntity(): ?JobSearchResult
    {
        return $this->savedOfferEntity;
    }

    /**
     * @param JobSearchResult|null $savedOfferEntity
     */
    public function setSavedOfferEntity(?JobSearchResult $savedOfferEntity): void
    {
        $this->savedOfferEntity = $savedOfferEntity;
    }

}