<?php

namespace JobSearcher\Service\Filters\Provider\JobOffers;

use JobSearcher\DTO\Api\Transport\Filter\CityFilterValueDto;
use JobSearcher\DTO\Api\Transport\Filter\FilterValuesDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\Normalizer\Location\CityNameNormalizer;

/**
 * Provides filter values based on the cities of the offers
 */
class CitiesFilterDataProvider implements OffersRelatedProviderInterface
{
    /**
     * @var JobOfferExtraction $extraction
     */
    private JobOfferExtraction $extraction;

    /**
     * Offers ids must be used in this context in order to show that given locations got no offers for current results set
     *
     * @var array $offerIds
     */
    private array $offerIds = [];

    /**
     * @return array
     */
    public function getOfferIds(): array
    {
        return $this->offerIds;
    }

    /**
     * @param array $offerIds
     */
    public function setOfferIds(array $offerIds): void
    {
        $this->offerIds = $offerIds;
    }

    /**
     * {@inheritDoc}
     *
     * @return JobOfferExtraction
     */
    public function getExtraction(): JobOfferExtraction
    {
        return $this->extraction;
    }

    /**
     * {@inheritDoc}
     *
     * @param JobOfferExtraction $extraction
     */
    public function setExtraction(JobOfferExtraction $extraction): void
    {
        $this->extraction = $extraction;
    }

    /**
     * {@inheritDoc}
     */
    public function provide(FilterValuesDto $filterValues): FilterValuesDto
    {
        $citiesFiltersDto = [];
        foreach ($this->extraction->getJobSearchResults() as $searchResult) {

            if (empty($searchResult->getCompanyBranch()?->getLocation()?->getName())) {
                continue;
            }

            $cityName      = CityNameNormalizer::normalize($searchResult->getCompanyBranch()->getLocation()->getName());
            $cityFilterDto = $citiesFiltersDto[$cityName] ?? null;
            if (empty($cityFilterDto)) {
                $cityFilterDto = new CityFilterValueDto($cityName);
                $citiesFiltersDto[$cityName] = $cityFilterDto;
            }
            $cityFilterDto->increaseCount();
        }

        $filterValues->setCityFilterValues($citiesFiltersDto);

        return $filterValues;
    }

}