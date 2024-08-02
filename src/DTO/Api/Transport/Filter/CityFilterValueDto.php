<?php

namespace JobSearcher\DTO\Api\Transport\Filter;

/**
 * Provides city based values to be used in filtering
 */
class CityFilterValueDto
{

    /**
     * This is not used for now.
     * In general, it was used on beginning, but there is an awkward issue
     * - sometimes locations on pages are wrong so company might already have branch saved in db for that location,
     * - now the locations were counted based on "CURRENT OFFER BRANCH LOCATION",
     * - while locations showed on detail card are "ALL BRANCHES LOCATIONS",
     *
     * So count of offers on select will mostly always be different from count on page, and still having more locations
     * is proper because it shows other locations for company.
     *
     * @var int $offersCount
     */
    private int $offersCount = 0;

    public function __construct(
        private readonly string $cityName,
    ) {
    }

    /**
     * @return string
     */
    public function getCityName(): string
    {
        return $this->cityName;
    }

    /**
     * @return int
     */
    public function getOffersCount(): int
    {
        return $this->offersCount;
    }

    public function increaseCount(): void
    {
        $this->offersCount++;
    }
}