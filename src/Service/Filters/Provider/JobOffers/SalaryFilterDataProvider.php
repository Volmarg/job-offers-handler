<?php

namespace JobSearcher\Service\Filters\Provider\JobOffers;

use JobSearcher\DTO\Api\Transport\Filter\FilterValuesDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;

/**
 * Provides filter values based on the salary of the offers
 */
class SalaryFilterDataProvider implements OffersRelatedProviderInterface
{
    /**
     * @var JobOfferExtraction $extraction
     */
    private JobOfferExtraction $extraction;

    /**
     * @var array $offerIds
     */
    private array $offerIds = [];

    /**
     * Not used in context of current provider, because there always got to be returned full
     * salary range
     *
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
        $allMaxSalaries = [];
        foreach ($this->extraction->getJobSearchResults() as $searchResult) {

            if (empty($searchResult->getSalaryMax())) {
                continue;
            }

            $maxSalary = (0 !== $searchResult->getSalaryMax() ? $searchResult->getSalaryMax() : $searchResult->getSalaryAverage());
            if (!empty($maxSalary)) {
                $allMaxSalaries[] = $maxSalary;
            }
        }

        if (empty($allMaxSalaries)) {
            return $filterValues;
        }

        $maxSalary = max($allMaxSalaries);

        $filterValues->setMaxSalary($maxSalary);

        return $filterValues;
    }
}