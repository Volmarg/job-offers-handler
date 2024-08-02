<?php

namespace JobSearcher\DTO\Api\Transport\Filter;

/**
 * Provides any kind of values that which can be used for filling up the filters data
 */
class FilterValuesDto
{
    /**
     * @var CityFilterValueDto[] $cityFilterValues
     */
    private array $cityFilterValues = [];

    /**
     * @var CountryFilterValueDto[] $countryFilterValues
     */
    private array $countryFilterValues = [];

    /**
     * @var int $minSalary
     */
    private int $minSalary = 0;

    /**
     * @var int $maxSalary
     */
    private int $maxSalary;

    /**
     * @return array
     */
    public function getCityFilterValues(): array
    {
        return $this->cityFilterValues;
    }

    /**
     * @param array $cityFilterValues
     */
    public function setCityFilterValues(array $cityFilterValues): void
    {
        $this->cityFilterValues = $cityFilterValues;
    }

    /**
     * @return int
     */
    public function getMinSalary(): int
    {
        return $this->minSalary;
    }

    /**
     * @param int $minSalary
     */
    public function setMinSalary(int $minSalary): void
    {
        $this->minSalary = $minSalary;
    }

    /**
     * @return int
     */
    public function getMaxSalary(): int
    {
        return $this->maxSalary;
    }

    /**
     * @param int $maxSalary
     */
    public function setMaxSalary(int $maxSalary): void
    {
        $this->maxSalary = $maxSalary;
    }

    /**
     * @return array
     */
    public function getCountryFilterValues(): array
    {
        return $this->countryFilterValues;
    }

    /**
     * @param array $countryFilterValues
     */
    public function setCountryFilterValues(array $countryFilterValues): void
    {
        $this->countryFilterValues = $countryFilterValues;
    }

}