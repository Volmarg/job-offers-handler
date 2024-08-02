<?php

namespace JobSearcher\Service\Filters\Provider\JobOffers;

use JobSearcher\DTO\Api\Transport\Filter\CountryFilterValueDto;
use JobSearcher\DTO\Api\Transport\Filter\FilterValuesDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;

/**
 * Provides filter values based on the countries of the offers
 */
class CountryFilterDataProvider implements OffersRelatedProviderInterface
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
        $countriesFiltersDto = [];
        foreach ($this->extraction->getJobSearchResults() as $searchResult) {

            if (empty($searchResult->getCompanyBranch()?->getLocation()?->getCountry())) {
                continue;
            }

            $countryFilterDto = $countriesFiltersDto[$searchResult->getCompanyBranch()->getLocation()->getCountry()] ?? null;
            if (empty($countryFilterDto)) {
                $countryFilterDto = new CountryFilterValueDto($searchResult->getCompanyBranch()->getLocation()->getCountry());
                $countriesFiltersDto[$countryFilterDto->getCountryName()] = $countryFilterDto;
            }

            if (in_array($searchResult->getId(), $this->getOfferIds())) {
                $countryFilterDto->increaseCount();
            }
        }

        $filterValues->setCountryFilterValues($countriesFiltersDto);

        return $filterValues;
    }

}