<?php

namespace JobSearcher\Response\Offer;

use JobSearcher\Action\API\Offers\OffersController;
use JobSearcher\DTO\Api\Transport\Filter\FilterValuesDto;
use JobSearcher\Response\BaseApiResponse;

/**
 * Related to the {@see OffersController::getOffers()}
 */
class GetJobOffersResponse extends BaseApiResponse
{
    /**
     * @var array $offersArray
     */
    private array $offersArray = [];

    /**
     * @var FilterValuesDto $filterValues
     */
    private FilterValuesDto $filterValues;

    /**
     * @var int $allFoundOffersCount
     */
    private int $allFoundOffersCount;

    /**
     * @var int $returnedOffersCount
     */
    private int $returnedOffersCount;

    /**
     * @param array $offersArray
     */
    public function setOffersArray(array $offersArray): void
    {
        $this->offersArray = $offersArray;
    }

    /**
     * @return array
     */
    public function getOffersArray(): array
    {
        return $this->offersArray;
    }

    /**
     * @return int
     */
    public function getAllFoundOffersCount(): int
    {
        return $this->allFoundOffersCount;
    }

    /**
     * @param int $allFoundOffersCount
     */
    public function setAllFoundOffersCount(int $allFoundOffersCount): void
    {
        $this->allFoundOffersCount = $allFoundOffersCount;
    }

    /**
     * @return int
     */
    public function getReturnedOffersCount(): int
    {
        return $this->returnedOffersCount;
    }

    /**
     * @param int $returnedOffersCount
     */
    public function setReturnedOffersCount(int $returnedOffersCount): void
    {
        $this->returnedOffersCount = $returnedOffersCount;
    }

    /**
     * @return FilterValuesDto
     */
    public function getFilterValues(): FilterValuesDto
    {
        return $this->filterValues;
    }

    /**
     * @param FilterValuesDto $filterValues
     */
    public function setFilterValues(FilterValuesDto $filterValues): void
    {
        $this->filterValues = $filterValues;
    }

}