<?php

/**
 * Handles filtering the offers by given rules
 */
namespace JobSearcher\Service\JobSearch\Result;

use JobSearcher\DTO\Api\Transport\Offer\ExcludedOfferData;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Service\JobSearch\Result\Filter\BaseFilter;
use JobSearcher\Service\JobSearch\Result\Filter\OfferFilterInterface;

/**
 * Handles filtering offers by given filter rules, {@see OfferFilterInterface}
 * These are some extra filters that have to be applied outside the repository {@see JobSearchResultRepository::addFilterDtoQueries()}
 */
class OffersFilterService
{

    /**
     * @param BaseFilter[] $filters
     */
    public function __construct(
        private readonly array $filters
    ){}

    /**
     * @param JobSearchResult[]       $offers
     * @param JobOfferFilterDto       $filterDto
     * @param ExcludedOfferData[]     $excludedOffersDtos
     * @param int[]                   $userExtractionIds
     * @param JobOfferExtraction|null $currentlyHandledExtraction
     *
     * @return JobSearchResult[]
     */
    public function applyFilters(
        array               $offers,
        JobOfferFilterDto   $filterDto,
        array               $excludedOffersDtos,
        array               $userExtractionIds,
        ?JobOfferExtraction $currentlyHandledExtraction = null
    ): array
    {
        $isFirstFilter  = true;
        $filteredOffers = [];
        foreach ($this->filters as $filter) {

            $filter->setOffers($filteredOffers);;
            if ($isFirstFilter) {
                $filter->setOffers($offers);
                $isFirstFilter = false;
            }

            $filter->setFilterDto($filterDto);
            $filter->setExcludedOffersDtos($excludedOffersDtos);
            $filter->setUserExtractionIds($userExtractionIds);
            $filter->setCurrentlyHandledExtraction($currentlyHandledExtraction);

            $filteredOffers = $filter->filter();
        }

        return $filteredOffers;
    }
}