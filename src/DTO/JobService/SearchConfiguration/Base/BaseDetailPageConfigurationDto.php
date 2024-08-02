<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base;

/**
 * Base detail page configuration dto for all handled ways of fetching data
 */
class BaseDetailPageConfigurationDto
{
    /**
     * @var string|null $baseHost
     */
    private ?string $baseHost;

    /**
     * @var string|null $baseUri
     */
    private ?string $baseUri;

    /**
     * @return string|null
     */
    public function getBaseHost(): ?string
    {
        return $this->baseHost;
    }

    /**
     * @param string|null $baseHost
     */
    public function setBaseHost(?string $baseHost): void
    {
        $this->baseHost = $baseHost;
    }

    /**
     * @return string|null
     */
    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }

    /**
     * @param string|null $baseUri
     */
    public function setBaseUri(?string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }

    /**
     * Will build child from {@see BaseDetailPageConfigurationDto}
     *
     * @param BaseDetailPageConfigurationDto $baseDetailPageConfigurationDto
     * @return static
     */
    public static function buildFromBaseDto(BaseDetailPageConfigurationDto $baseDetailPageConfigurationDto): static
    {
        $searchDetailPageConfigurationDto = new static();
        $searchDetailPageConfigurationDto->setBaseHost($baseDetailPageConfigurationDto->getBaseHost());
        $searchDetailPageConfigurationDto->setBaseUri($baseDetailPageConfigurationDto->getBaseUri());

        return $searchDetailPageConfigurationDto;
    }

}