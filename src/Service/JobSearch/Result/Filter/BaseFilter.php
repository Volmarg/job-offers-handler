<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use JobSearcher\DTO\Api\Transport\Offer\ExcludedOfferData;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;

/**
 * Base filter for all the offer filters
 */
abstract class BaseFilter implements OfferFilterInterface
{
    /**
     * @var JobSearchResult[]
     */
    private array $offers = [];

    /**
     * @var JobOfferFilterDto $filterDto
     */
    private JobOfferFilterDto $filterDto;

    /**
     * @var ExcludedOfferData[] $excludedOffersDtos
     */
    private array $excludedOffersDtos = [];

    /**
     * @var int[] $userExtractionIds
     */
    private array $userExtractionIds = [];

    /**
     * @var JobOfferExtraction|null
     */
    private ?JobOfferExtraction $currentlyHandledExtraction = null;

    /**
     * @return array
     */
    public function getOffers(): array
    {
        return $this->offers;
    }

    /**
     * @param array $offers
     */
    public function setOffers(array $offers): void
    {
        $this->offers = $offers;
    }

    /**
     * @return JobOfferFilterDto
     */
    public function getFilterDto(): JobOfferFilterDto
    {
        return $this->filterDto;
    }

    /**
     * @param JobOfferFilterDto $filterDto
     */
    public function setFilterDto(JobOfferFilterDto $filterDto): void
    {
        $this->filterDto = $filterDto;
    }

    /**
     * @return array
     */
    public function getExcludedOffersDtos(): array
    {
        return $this->excludedOffersDtos;
    }

    /**
     * @param array $excludedOffersDtos
     */
    public function setExcludedOffersDtos(array $excludedOffersDtos): void
    {
        $this->excludedOffersDtos = $excludedOffersDtos;
    }

    /**
     * @return array
     */
    public function getUserExtractionIds(): array
    {
        return $this->userExtractionIds;
    }

    /**
     * @param array $userExtractionIds
     */
    public function setUserExtractionIds(array $userExtractionIds): void
    {
        $this->userExtractionIds = $userExtractionIds;
    }

    /**
     * @return JobOfferExtraction|null
     */
    public function getCurrentlyHandledExtraction(): ?JobOfferExtraction
    {
        return $this->currentlyHandledExtraction;
    }

    /**
     * @param JobOfferExtraction|null $currentlyHandledExtraction
     */
    public function setCurrentlyHandledExtraction(?JobOfferExtraction $currentlyHandledExtraction): void
    {
        $this->currentlyHandledExtraction = $currentlyHandledExtraction;
    }

}