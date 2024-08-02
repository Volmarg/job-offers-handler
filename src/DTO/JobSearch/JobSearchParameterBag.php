<?php

namespace JobSearcher\DTO\JobSearch;

use JobSearcher\Command\JobSearch\AllJobOffersExtractorCommand;
use JobSearcher\Command\JobSearch\SingleConfigurationJobOffersExtractorCommand;

/**
 * Transmits the most necessary search data such as keywords / location etc.
 */
class JobSearchParameterBag
{
    public function __construct(
        private array   $keywords = [],
        private int     $paginationPagesCount = 0,
        private ?int    $distance = null,
        private ?string $location = null,

        /**
         * Country is nullable due to {@see SingleConfigurationJobOffersExtractorCommand} which is used only for debugging,
         * the {@see AllJobOffersExtractorCommand} throws exception if country is not set, and that one is called in normal process.
         */
        private ?string $country = null,
        private ?int $offersLimit = null,
    ){}

    /**
     * @return array
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * @return string
     */
    public function getKeywordsAsString(): string
    {
        return implode(",", $this->getKeywords());
    }

    /**
     * @param array $keywords
     */
    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * @return int
     */
    public function getPaginationPagesCount(): int
    {
        return $this->paginationPagesCount;
    }

    /**
     * @param int $paginationPagesCount
     */
    public function setPaginationPagesCount(int $paginationPagesCount): void
    {
        $this->paginationPagesCount = $paginationPagesCount;
    }

    /**
     * @return int|null
     */
    public function getDistance(): ?int
    {
        return $this->distance;
    }

    /**
     * @param int|null $distance
     */
    public function setDistance(?int $distance): void
    {
        $this->distance = $distance;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @param string|null $location
     */
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     */
    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getOffersLimit(): ?int
    {
        return $this->offersLimit;
    }

    public function setOffersLimit(?int $offersLimit): void
    {
        $this->offersLimit = $offersLimit;
    }

    /**
     * @return bool
     */
    public function isDistanceSet(): bool
    {
        return !empty($this->getDistance());
    }

}