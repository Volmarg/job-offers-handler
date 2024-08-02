<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriBase\BaseSearchUriDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation\BaseLocationDistance;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation\BaseLocationName;
use JobSearcher\Service\JobService\ConfigurationBuilder\ConfigurationBuilderInterface;

/**
 * Base search uri configuration dto for all handled ways of fetching data
 */
class BaseSearchUriConfigurationDto
{
    /**
     * @var int|null $paginationStartValue
     */
    private ?int $paginationStartValue = null;

    /**
     * This is string on purpose, because in some case some services might accept numeric page (0..1..2.),
     * in other hover it might be nothing (blank string = ""),
     *
     * Null indicates that it's not used,
     *
     * @var string|null $paginationFirstPageValue
     */
    private ?string $paginationFirstPageValue;

    /**
     * @var int|null $paginationIncrementValue
     */
    private ?int $paginationIncrementValue = null;

    /**
     * @var string $paginationNumberQueryParameter
     */
    private string $paginationNumberQueryParameter;

    /**
     * @var string $multipleKeyWordsSeparatorCharacter
     */
    private string $multipleKeyWordsSeparatorCharacter;

    /**
     * @var string|null $paginationSpacebarInKeywordWordsReplaceCharacter
     */
    private ?string $paginationSpacebarInKeywordWordsReplaceCharacter = null;

    /**
     * @var BaseSearchUriDto $baseSearchUri
     */
    private BaseSearchUriDto $baseSearchUri;

    /**
     * @var string|null $searchUriBaseHost
     */
    private ?string $searchUriBaseHost;

    /**
     * @var string|null
     */
    private ?string $keywordsPlacement = ConfigurationBuilderInterface::KEYWORDS_PLACEMENT_QUERY;

    /**
     * @var array $structure
     */
    private array $structure = [];

    /**
     * @var string|null
     */
    private ?string $resolver = null;

    /**
     * Has to be set initially to false as for example API configs mostly rely on NON encoded query
     * @var bool $encodeQuery
     */
    private bool $encodeQuery = false;

    public function __construct(
        private BaseLocationDistance $locationDistanceConfiguration = new BaseLocationDistance(),
        private BaseLocationName     $locationNameConfiguration = new BaseLocationName()
    ){}

    /**
     * @return string
     */
    public function getPaginationNumberQueryParameter(): string
    {
        return $this->paginationNumberQueryParameter;
    }

    /**
     * @param string $paginationNumberQueryParameter
     */
    public function setPaginationNumberQueryParameter(string $paginationNumberQueryParameter): void
    {
        $this->paginationNumberQueryParameter = $paginationNumberQueryParameter;
    }

    /**
     * @return string
     */
    public function getMultipleKeyWordsSeparatorCharacter(): string
    {
        return $this->multipleKeyWordsSeparatorCharacter;
    }

    /**
     * @param string $multipleKeyWordsSeparatorCharacter
     */
    public function setMultipleKeyWordsSeparatorCharacter(string $multipleKeyWordsSeparatorCharacter): void
    {
        $this->multipleKeyWordsSeparatorCharacter = $multipleKeyWordsSeparatorCharacter;
    }

    /**
     * @return BaseSearchUriDto
     */
    public function getBaseSearchUri(): BaseSearchUriDto
    {
        return $this->baseSearchUri;
    }

    /**
     * @param BaseSearchUriDto $baseSearchUri
     */
    public function setBaseSearchUri(BaseSearchUriDto $baseSearchUri): void
    {
        $this->baseSearchUri = $baseSearchUri;
    }

    /**
     * @return string|null
     */
    public function getSearchUriBaseHost(): ?string
    {
        return $this->searchUriBaseHost;
    }

    /**
     * @param string|null $searchUriBaseHost
     */
    public function setSearchUriBaseHost(?string $searchUriBaseHost): void
    {
        $this->searchUriBaseHost = $searchUriBaseHost;
    }

    /**
     * @return string|null
     */
    public function getKeywordsPlacement(): ?string
    {
        return $this->keywordsPlacement;
    }

    /**
     * @param string|null $keywordsPlacement
     */
    public function setKeywordsPlacement(?string $keywordsPlacement): void
    {
        $this->keywordsPlacement = $keywordsPlacement;
    }

    /**
     * @return bool
     */
    public function isKeywordsPlacedInQuery(): bool
    {
        return ConfigurationBuilderInterface::KEYWORDS_PLACEMENT_QUERY === $this->keywordsPlacement;
    }

    /**
     * @return bool
     */
    public function isKeywordsPlacementRequestBody(): bool
    {
        return ConfigurationBuilderInterface::KEYWORDS_PLACEMENT_REQUEST_BODY === $this->keywordsPlacement;
    }

    /**
     * @return array
     */
    public function getStructure(): array
    {
        return $this->structure;
    }

    /**
     * @param array $structure
     */
    public function setStructure(array $structure): void
    {
        $this->structure = $structure;
    }

    /**
     * @return bool
     */
    public function isEncodeQuery(): bool
    {
        return $this->encodeQuery;
    }

    /**
     * @param bool $encodeQuery
     */
    public function setEncodeQuery(bool $encodeQuery): void
    {
        $this->encodeQuery = $encodeQuery;
    }

    /**
     * @return BaseLocationDistance
     */
    public function getLocationDistanceConfiguration(): BaseLocationDistance
    {
        return $this->locationDistanceConfiguration;
    }

    /**
     * @param BaseLocationDistance $locationDistanceConfiguration
     */
    public function setLocationDistanceConfiguration(BaseLocationDistance $locationDistanceConfiguration): void
    {
        $this->locationDistanceConfiguration = $locationDistanceConfiguration;
    }

    /**
     * @return BaseLocationName
     */
    public function getLocationNameConfiguration(): BaseLocationName
    {
        return $this->locationNameConfiguration;
    }

    /**
     * @param BaseLocationName $locationNameConfiguration
     */
    public function setLocationNameConfiguration(BaseLocationName $locationNameConfiguration): void
    {
        $this->locationNameConfiguration = $locationNameConfiguration;
    }

    /**
     * @return string|null
     */
    public function getResolver(): ?string
    {
        return $this->resolver;
    }

    /**
     * @param string|null $resolver
     */
    public function setResolver(?string $resolver): void
    {
        $this->resolver = $resolver;
    }

    /**
     * @return bool
     */
    public function isUsingResolver(): bool
    {
        return !empty($this->getResolver());
    }

    /**
     * @return string|null
     */
    public function getPaginationFirstPageValue(): ?string
    {
        return $this->paginationFirstPageValue;
    }

    /**
     * @param string|null $paginationFirstPageValue
     */
    public function setPaginationFirstPageValue(?string $paginationFirstPageValue): void
    {
        $this->paginationFirstPageValue = $paginationFirstPageValue;
    }

    public function getPaginationSpacebarInKeywordWordsReplaceCharacter(): ?string
    {
        return $this->paginationSpacebarInKeywordWordsReplaceCharacter;
    }

    public function setPaginationSpacebarInKeywordWordsReplaceCharacter(?string $paginationSpacebarInKeywordWordsReplaceCharacter): void {
        $this->paginationSpacebarInKeywordWordsReplaceCharacter = $paginationSpacebarInKeywordWordsReplaceCharacter;
    }

    public function getPaginationStartValue(): ?int
    {
        return $this->paginationStartValue;
    }

    public function setPaginationStartValue(?int $paginationStartValue): void
    {
        $this->paginationStartValue = $paginationStartValue;
    }

    public function getPaginationIncrementValue(): ?int
    {
        return $this->paginationIncrementValue;
    }

    public function setPaginationIncrementValue(?int $paginationIncrementValue): void
    {
        $this->paginationIncrementValue = $paginationIncrementValue;
    }

    /**
     * Will build child dto from the base dto
     *
     * @param BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto
     * @return static
     */
    public static function buildFromBaseDto(BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto): static
    {
        $searchUriConfigurationDto = new static();
        $searchUriConfigurationDto->setPaginationFirstPageValue($baseSearchUriConfigurationDto->getPaginationFirstPageValue());
        $searchUriConfigurationDto->setPaginationStartValue($baseSearchUriConfigurationDto->getPaginationStartValue());
        $searchUriConfigurationDto->setPaginationIncrementValue($baseSearchUriConfigurationDto->getPaginationIncrementValue());
        $searchUriConfigurationDto->setPaginationNumberQueryParameter($baseSearchUriConfigurationDto->getPaginationNumberQueryParameter());
        $searchUriConfigurationDto->setMultipleKeyWordsSeparatorCharacter($baseSearchUriConfigurationDto->getMultipleKeyWordsSeparatorCharacter());
        $searchUriConfigurationDto->setPaginationSpacebarInKeywordWordsReplaceCharacter($baseSearchUriConfigurationDto->getPaginationSpacebarInKeywordWordsReplaceCharacter());
        $searchUriConfigurationDto->setBaseSearchUri($baseSearchUriConfigurationDto->getBaseSearchUri());
        $searchUriConfigurationDto->setResolver($baseSearchUriConfigurationDto->getResolver());
        $searchUriConfigurationDto->setSearchUriBaseHost($baseSearchUriConfigurationDto->getSearchUriBaseHost());
        $searchUriConfigurationDto->setKeywordsPlacement($baseSearchUriConfigurationDto->getKeywordsPlacement());
        $searchUriConfigurationDto->setStructure($baseSearchUriConfigurationDto->getStructure());
        $searchUriConfigurationDto->setLocationNameConfiguration($baseSearchUriConfigurationDto->getLocationNameConfiguration());
        $searchUriConfigurationDto->setLocationDistanceConfiguration($baseSearchUriConfigurationDto->getLocationDistanceConfiguration());

        return $searchUriConfigurationDto;
    }

}