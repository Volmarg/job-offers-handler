<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation;

use JobSearcher\Service\JobService\ConfigurationBuilder\Constants\LocationSearchUriConstants;
use LogicException;

/**
 * Contains common logic for location name/distance based search
 */
abstract class BaseLocation
{
    public function __construct(
        private ?string $placement = null,
        private ?string $queryParameter = null,
        private bool    $trailingSlash = false,
        private ?string $formatterFunction = null,
        private ?string $locationSpacebarReplaceCharacter = null
    )
    {}

    /**
     * @return string|null
     */
    public function getPlacement(): ?string
    {
        return $this->placement;
    }

    /**
     * @param string|null $placement
     */
    public function setPlacement(?string $placement): void
    {
        $this->placement = $placement;
    }

    /**
     * @return string|null
     */
    public function getQueryParameter(): ?string
    {
        return $this->queryParameter;
    }

    /**
     * @param string|null $queryParameter
     */
    public function setQueryParameter(?string $queryParameter): void
    {
        $this->queryParameter = $queryParameter;
    }

    /**
     * @return bool
     */
    public function isTrailingSlash(): bool
    {
        return $this->trailingSlash;
    }

    /**
     * @param bool $trailingSlash
     */
    public function setTrailingSlash(bool $trailingSlash): void
    {
        $this->trailingSlash = $trailingSlash;
    }

    /**
     * @return bool
     */
    public function isQueryParamBased(): bool
    {
        return ($this->getPlacement() === LocationSearchUriConstants::LOCATION_PLACEMENT_QUERY);
    }

    /**
     * @return bool
     */
    public function isUriPartBased(): bool
    {
        return ($this->getPlacement() === LocationSearchUriConstants::LOCATION_PLACEMENT_URI_PART);
    }

    /**
     * @return bool
     */
    public function isNotBeingSetInQuery(): bool
    {
        return empty($this->getPlacement());
    }

    /**
     * Validate the state of base location
     */
    public function validateSelf(): void
    {
        if(
                 $this->isQueryParamBased()
            &&  empty($this->getQueryParameter())
        ){
            throw new LogicException("Placement is marked as Query based yet the query parameter is missing");
        }

        if(
                !$this->isQueryParamBased()
            &&  !empty($this->getQueryParameter())
        ){
            throw new LogicException("Placement is marked as NOT Query based yet the query parameter is set");
        }
    }

    public function getFormatterFunction(): ?string
    {
        return $this->formatterFunction;
    }

    /**
     * @return string|null
     */
    public function getLocationSpacebarReplaceCharacter(): ?string
    {
        return $this->locationSpacebarReplaceCharacter;
    }

}