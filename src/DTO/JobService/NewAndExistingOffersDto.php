<?php

namespace JobSearcher\DTO\JobService;

use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;

class NewAndExistingOffersDto
{
    public function __construct(
        private array $existingOfferEntities = [],
        private array $allSearchResultDtos      = []
    ){}

    /**
     * @return JobSearchResult[]
     */
    public function getExistingOfferEntities(): array
    {
        return $this->existingOfferEntities;
    }

    /**
     * @return SearchResultDto[]
     */
    public function getAllSearchResultDtos(): array
    {
        return $this->allSearchResultDtos;
    }

    /**
     * @param JobSearchResult[] $existingOfferEntities
     */
    public function setExistingOfferEntities(array $existingOfferEntities): void
    {
        $this->existingOfferEntities = $existingOfferEntities;
    }

    /**
     * @param SearchResultDto[] $allSearchResultDtos
     */
    public function setAllSearchResultDtos(array $allSearchResultDtos): void
    {
        $this->allSearchResultDtos = $allSearchResultDtos;
    }

    /**
     * @param SearchResultDto $resultDto
     */
    public function addNewOffer(SearchResultDto $resultDto): void
    {
        $this->allSearchResultDtos[] = $resultDto;
    }

    /**
     * @param JobSearchResult $resultDto
     */
    public function addExistingOffer(JobSearchResult $resultDto): void
    {
        $this->existingOfferEntities[] = $resultDto;
    }

    /**
     * @return int
     */
    public function countExistingOffers(): int
    {
        return count($this->getExistingOfferEntities());
    }

    /**
     * @return int
     */
    public function countAllOffers(): int
    {
        return count($this->getAllSearchResultDtos()) + $this->countExistingOffers();
    }

}