<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Api;

use JobSearcher\DTO\JobSearch\Api\HeaderDto;
use JobSearcher\DTO\JobSearch\Api\RawBodyParametersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;

/**
 * Search uri configuration for Api based data fetching
 */
class SearchUriConfigurationDto extends BaseSearchUriConfigurationDto
{
    /**
     * @var string $method
     */
    private string $method;

    /**
     * @var RawBodyParametersDto[] $requestRawBody
     */
    private array $requestRawBody;

    /**
     * @var HeaderDto[] $requestHeaders
     */
    private array $requestHeaders;

    private ?string $scrapEngine = null;

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return RawBodyParametersDto[]
     */
    public function getRequestRawBody(): array
    {
        return $this->requestRawBody;
    }

    /**
     * @param RawBodyParametersDto[] $requestRawBody
     */
    public function setRequestRawBody(array $requestRawBody): void
    {
        $this->requestRawBody = $requestRawBody;
    }

    /**
     * @return HeaderDto[]
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    /**
     * @param HeaderDto[] $requestHeaders
     */
    public function setRequestHeaders(array $requestHeaders): void
    {
        $this->requestHeaders = $requestHeaders;
    }

    public function getScrapEngine(): ?string
    {
        return $this->scrapEngine;
    }

    public function setScrapEngine(?string $scrapEngine): void
    {
        $this->scrapEngine = $scrapEngine;
    }

}