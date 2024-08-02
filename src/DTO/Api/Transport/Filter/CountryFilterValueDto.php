<?php

namespace JobSearcher\DTO\Api\Transport\Filter;

/**
 * Provides country based values to be used in filtering
 */
class CountryFilterValueDto
{

    /**
     * @var int $offersCount
     */
    private int $offersCount = 0;

    public function __construct(
        private readonly string $countryName,
    ) {
    }

    /**
     * @return string
     */
    public function getCountryName(): string
    {
        return $this->countryName;
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