<?php

namespace JobSearcher\Service\JobSearch\Extractor\Api;

use JobSearcher\DTO\JobSearch\Api\HeaderDto;
use JobSearcher\DTO\JobSearch\Api\RawBodyParametersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto;
use JobSearcher\Exception\JobServiceCallableResolverException;
use JobSearcher\Service\JobService\Resolver\JobServiceCallableResolver;
use JobSearcher\Service\JobService\Resolver\ParametersEnum;
use LogicException;

/**
 * Handles resolving values for the {@see ExtractorService}
 */
class ExtractorResolverService
{
    /**
     * @var MainConfigurationDto $mainConfigurationDto
     */
    private MainConfigurationDto $mainConfigurationDto;

    /**
     * @var array $keywords
     */
    private array $keywords;

    /**
     * @var string|null $locationName ;
     */
    private ?string $locationName = null;

    /**
     * @var int|null $locationDistance
     */
    private ?int $locationDistance = null;

    /**
     * @var int $maxPaginationPages
     */
    private int $maxPaginationPages;

    /**
     * @var array $searchResultData
     */
    private array $searchResultData = [];

    /**
     * @var array $searchPageRequestBodyData
     */
    private array $searchPageRequestBodyData = [];

    /**
     * @param array $searchPageRequestBodyData
     */
    public function setSearchPageRequestBodyData(array $searchPageRequestBodyData): void
    {
        $this->searchPageRequestBodyData = $searchPageRequestBodyData;
    }

    /**
     * @param array $searchResultData
     */
    public function setSearchResultData(array $searchResultData): void
    {
        $this->searchResultData = $searchResultData;
    }

    /**
     * @param array $keywords
     */
    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string|null
     */
    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * @param string|null $locationName
     */
    public function setLocationName(?string $locationName): void
    {
        $this->locationName = $locationName;
    }

    /**
     * @return int|null
     */
    public function getLocationDistance(): ?int
    {
        return $this->locationDistance;
    }

    /**
     * @param int|null $locationDistance
     */
    public function setLocationDistance(?int $locationDistance): void
    {
        $this->locationDistance = $locationDistance;
    }

    /**
     * @param int $maxPaginationPages
     */
    public function setMaxPaginationPages(int $maxPaginationPages): void
    {
        $this->maxPaginationPages = $maxPaginationPages;
    }

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     */
    public function setMainConfigurationDto(MainConfigurationDto $mainConfigurationDto): void
    {
        $this->mainConfigurationDto = $mainConfigurationDto;
    }

    public function __construct(
        private JobServiceCallableResolver $jobServiceCallableResolver
    ){}

    /**
     * @param RawBodyParametersDto[] $bodyDtos
     *
     * @return void
     * @throws JobServiceCallableResolverException
     */
    public function resolveRequestBodyParameters(array $bodyDtos): void
    {
        foreach ($bodyDtos as $requestBodyParameter) {
            $this->resolveOneRequestBodyParameter($requestBodyParameter);
        }
    }

    /**
     * Will make sure that the required variables are set and ready to be used
     */
    public function ensureVariableSet(): void
    {
        if (!isset($this->mainConfigurationDto)) {
            throw new LogicException("Main configuration dto is not set!");
        }

        if (!isset($this->keywords)) {
            throw new LogicException("Keywords are not set!");
        }

        if (!isset($this->maxPaginationPages)) {
            throw new LogicException("Max pagination pages count is not set!");
        }
    }

    /**
     * Handles resolving values for headers, using:
     * - {@see JobServiceCallableResolver}
     *
     * @param HeaderDto[] $headerDtos
     *
     * @throws JobServiceCallableResolverException
     */
    public function resolveHeaders(array $headerDtos): void
    {
        foreach ($headerDtos as $headerDto) {
            $this->jobServiceCallableResolver->setClassMethodString($headerDto->getValue());
            $resolvedValue = $this->jobServiceCallableResolver->resolveValue($this->getResolverParameters());
            if (is_null($resolvedValue)) {
                continue;
            }

            $headerDto->setValue($resolvedValue);
        }
    }

    /**
     * Will resolve values for single {@see RawBodyParametersDto} alongside with its children,
     * this method is required to handle the recursion over the children
     *
     * @param RawBodyParametersDto $requestBodyParameter
     *
     * @throws JobServiceCallableResolverException
     */
    private function resolveOneRequestBodyParameter(RawBodyParametersDto $requestBodyParameter): void
    {
        $this->jobServiceCallableResolver->setClassMethodString($requestBodyParameter->getValue());
        $resolvedValue = $this->jobServiceCallableResolver->resolveValue($this->getResolverParameters());
        if (!is_null($resolvedValue)) {
            $requestBodyParameter->setValue($resolvedValue);
        }

        foreach ($requestBodyParameter->getChildren() as $child) {
            $this->resolveOneRequestBodyParameter($child);
        }

    }

    /**
     * @return array
     */
    private function getResolverParameters(): array
    {
        $paginationParameters = [
            ParametersEnum::KEYWORDS->name                      => $this->keywords,
            ParametersEnum::MAIN_CONFIGURATION_DTO->name        => $this->mainConfigurationDto,
            ParametersEnum::MAX_PAGINATION_PAGES->name          => $this->maxPaginationPages,
            ParametersEnum::SEARCH_RESULT_DATA->name            => $this->searchResultData,
            ParametersEnum::SEARCH_PAGE_REQUEST_BODY_DATA->name => $this->searchPageRequestBodyData,
            ParametersEnum::LOCATION_NAME->name                 => $this->locationName,
            ParametersEnum::LOCATION_DISTANCE->name             => $this->locationDistance,
        ];

        return $paginationParameters;
    }
}