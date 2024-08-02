<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Api;

use ContainerJBdRcVv\EntityManager_9a5be93;
use JobSearcher\DTO\JobSearch\Api\HeaderDto;
use JobSearcher\DTO\JobSearch\Api\RawBodyParametersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseDetailPageConfigurationDto;
use JobSearcher\Service\JobService\ConfigurationBuilder\Api\ApiConfigurationBuilderInterface;
use LogicException;

/**
 * Represents job service search configuration of the detail page for API based fetching
 */
class DetailPageConfigurationDto extends BaseDetailPageConfigurationDto
{
    /**
     * @var string|null $method
     */
    private ?string $method;
    /**
     * @var RawBodyParametersDto[] $requestRawBody
     */
    private array $requestRawBody;
    /**
     * @var HeaderDto[] $requestHeaders
     */
    private array $requestHeaders;

    /**
     * @var string|null $identifierPlacement
     */
    private ?string $identifierPlacement;

    /**
     * @var bool $identifierAfterSlash
     */
    private bool $identifierAfterSlash = true;

    /**
     * @var string|null $hostUriGlueString
     */
    private ?string $hostUriGlueString;

    /**
     * @var string|null
     */
    private ?string $offerDataResolver = null;

    /**
     * @var array $descriptionRemovedElementsSelectors
     */
    private array $descriptionRemovedElementsSelectors = [];

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @param string|null $method
     */
    public function setMethod(?string $method): void
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

    public function isIdentifierAfterSlash(): bool
    {
        return $this->identifierAfterSlash;
    }

    public function setIdentifierAfterSlash(bool $identifierAfterSlash): void
    {
        $this->identifierAfterSlash = $identifierAfterSlash;
    }

    /**
     * @return string|null
     */
    public function getIdentifierPlacement(): ?string
    {
        return $this->identifierPlacement;
    }

    /**
     * @param string|null $identifierPlacement
     */
    public function setIdentifierPlacement(?string $identifierPlacement): void
    {
        $this->identifierPlacement = $identifierPlacement;
    }

    /**
     * @return string|null
     */
    public function getHostUriGlueString(): ?string
    {
        return $this->hostUriGlueString;
    }

    /**
     * @param string|null $hostUriGlueString
     */
    public function setHostUriGlueString(?string $hostUriGlueString): void
    {
        $this->hostUriGlueString = $hostUriGlueString;
    }

    /**
     * For more information: {@see ApiConfigurationBuilderInterface::DETAIL_PAGE_IDENTIFIER_PLACEMENT_URI}
     *
     * @return bool
     */
    public function isIdentifierPlacementUri(): bool
    {
        return ($this->getIdentifierPlacement() === ApiConfigurationBuilderInterface::DETAIL_PAGE_IDENTIFIER_PLACEMENT_URI);
    }

    /**
     * For more information: {@see ApiConfigurationBuilderInterface::DETAIL_PAGE_IDENTIFIER_PLACEMENT_RAW_BODY}
     *
     * @return bool
     */
    public function isIdentifierPlacementRawBody(): bool
    {
        return ($this->getIdentifierPlacement() === ApiConfigurationBuilderInterface::DETAIL_PAGE_IDENTIFIER_PLACEMENT_RAW_BODY);
    }

    /**
     * @return string|null
     */
    public function getOfferDataResolver(): ?string
    {
        return $this->offerDataResolver;
    }

    /**
     * @param string|null $offerDataResolver
     */
    public function setOfferDataResolver(?string $offerDataResolver): void
    {
        $this->offerDataResolver = $offerDataResolver;
    }

    /**
     * @return bool
     */
    public function isUsingResolver(): bool
    {
        return !empty($this->getOfferDataResolver());
    }

    /**
     * @return array
     */
    public function getDescriptionRemovedElementsSelectors(): array
    {
        return $this->descriptionRemovedElementsSelectors;
    }

    /**
     * @param array $descriptionRemovedElementsSelectors
     */
    public function setDescriptionRemovedElementsSelectors(array $descriptionRemovedElementsSelectors): void
    {
        $this->descriptionRemovedElementsSelectors = $descriptionRemovedElementsSelectors;
    }

    /**
     * Check if the detail page can be scrapped with given configurations, in some cases it just desired to skip
     * extraction if configuration says so. Use case:
     *
     * it can happen that all the data for job offers is already delivered from the "search/pagination"
     * endpoint, if so then it's possible to set "host" & "uri" of detail page to null in order to skip
     * fetching its data
     *
     * @return bool
     */
    public function isScrappable(): bool
    {
        return (
            (
                    !empty($this->getBaseHost())
                &&  !empty($this->getBaseUri()
            )
            ||  $this->isUsingResolver()
            )
        );
    }

    /**
     * Will check the state of the configuration
     *
     * @return void
     */
    public function validateSelf(): void
    {
        if (
                $this->isScrappable()
            &&  (
                    (
                            !$this->isUsingResolver()
                        &&  (
                                empty($this->getMethod())
                            ||  empty($this->getIdentifierPlacement())
                        )
                    )
                    ||
                    (
                            $this->isUsingResolver()
                        &&  empty($this->getBaseHost())
                    )
                )
        ) {
            throw new LogicException("The configuration makes it Scrappable (host / base uri are there), yet the request `method` / `identifier placement` are missing");
        }
    }

}