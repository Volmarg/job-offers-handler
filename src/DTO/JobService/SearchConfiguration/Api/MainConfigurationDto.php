<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Api;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseMainConfigurationDto;

/**
 * Represents job service search configuration for API based fetching
 */
class MainConfigurationDto extends BaseMainConfigurationDto
{

    /**
     * @var SearchUriConfigurationDto $searchUriConfigurationDto
     */
    private SearchUriConfigurationDto $searchUriConfigurationDto;

    /**
     * @var DetailPageConfigurationDto $detailPageConfigurationDto
     */
    private DetailPageConfigurationDto $detailPageConfigurationDto;

    /**
     * @var JsonStructureConfigurationDto $jsonStructureConfigurationDto
     */
    private JsonStructureConfigurationDto $jsonStructureConfigurationDto;

    /**
     * @var int|null $crawlDelay
     */
    private ?int $crawlDelay;

    /**
     * @return DetailPageConfigurationDto
     */
    public function getDetailPageConfigurationDto(): DetailPageConfigurationDto
    {
        return $this->detailPageConfigurationDto;
    }

    /**
     * @param DetailPageConfigurationDto $detailPageConfigurationDto
     */
    public function setDetailPageConfigurationDto(DetailPageConfigurationDto $detailPageConfigurationDto): void {
        $this->detailPageConfigurationDto = $detailPageConfigurationDto;
    }

    /**
     * @return JsonStructureConfigurationDto
     */
    public function getJsonStructureConfigurationDto(): JsonStructureConfigurationDto
    {
        return $this->jsonStructureConfigurationDto;
    }

    /**
     * @param JsonStructureConfigurationDto $jsonStructureConfigurationDto
     */
    public function setJsonStructureConfigurationDto(JsonStructureConfigurationDto $jsonStructureConfigurationDto): void
    {
        $this->jsonStructureConfigurationDto = $jsonStructureConfigurationDto;
    }

    /**
     * @return SearchUriConfigurationDto
     */
    public function getSearchUriConfigurationDto(): SearchUriConfigurationDto
    {
        return $this->searchUriConfigurationDto;
    }

    /**
     * @param SearchUriConfigurationDto $searchUriConfigurationDto
     */
    public function setSearchUriConfigurationDto(SearchUriConfigurationDto $searchUriConfigurationDto): void
    {
        $this->searchUriConfigurationDto = $searchUriConfigurationDto;
    }

    /**
     * @return int|null
     */
    public function getCrawlDelay(): ?int
    {
        return $this->crawlDelay;
    }

    /**
     * @param int|null $crawlDelay
     */
    public function setCrawlDelay(?int $crawlDelay): void
    {
        $this->crawlDelay = $crawlDelay;
    }

}