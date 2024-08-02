<?php

namespace JobSearcher\Service\JobSearch\Command\Extractor;

use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\JobSearch\Command\Extractor\Api\ExtractorService     as ExtractorServiceApi;
use JobSearcher\Service\JobSearch\Command\Extractor\DomHtml\ExtractorService as ExtractorServiceDomHtml;
use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Service\JobService\ConfigurationBuilder\Api\ApiConfigurationBuilder;
use JobSearcher\Service\JobService\ConfigurationBuilder\DomHtml\DomHtmlConfigurationBuilder;

/**
 * Defines common logic for Extractor controllers which handle data extraction and analyze for different sources
 */
interface ExtractorInterface
{
    const EXTRACTION_SOURCE_API = "API";
    const EXTRACTION_SOURCE_DOM = "DOM";

    const ALL_AVAILABLE_EXTRACTION_SOURCES = [
        self::EXTRACTION_SOURCE_API,
        self::EXTRACTION_SOURCE_DOM,
    ];

    const SOURCE_TO_EXTRACTOR_CONTROLLER_MAPPING = [
        self::EXTRACTION_SOURCE_DOM => ExtractorServiceDomHtml::class,
        self::EXTRACTION_SOURCE_API => ExtractorServiceApi::class,
    ];

    const SOURCE_TO_CONFIG_BUILDER_MAPPING = [
        self::EXTRACTION_SOURCE_DOM => DomHtmlConfigurationBuilder::class,
        self::EXTRACTION_SOURCE_API => ApiConfigurationBuilder::class,
    ];

    /**
     * Will return name of the source used for extracting the data (API / DOM etc.)
     *
     * @return string
     */
    public function getExtractionSourceName(): string;

    /**
     * Will check if there are any configurations active for given extractor
     *
     * @param string|null $country
     *
     * @return bool
     */
    public function hasAnyConfigurationActive(?string $country): bool;

    /**
     * Will return array of {@see SearchResultDto} after analyze for source type: DOM HTML
     * which means that data was scrapped directly from the page DOm
     *
     * @param array              $keywords
     * @param int                $maxPaginationPagesToScrap
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @return NewAndExistingOffersDto[]
     */
    public function getOffersForAllConfigurations(array $keywords, int $maxPaginationPagesToScrap, JobOfferExtraction $jobOfferExtraction): array;

    /**
     * Returns all the active configuration names
     *
     * @param string $country
     *
     * @return array
     */
    public function getAllConfigurationNames(string $country): array;

}