<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation;

use JobSearcher\Service\JobService\ConfigurationBuilder\Constants\LocationSearchUriConstants;

/**
 * Handles the location distance based search in uri
 * Related to {@see BaseLocationDistance}
 */
class BaseLocationName extends BaseLocation
{
    public function __construct(
        private ?string $placement = null,
        private ?string $queryParameter = null,
        private string  $uriPartPrefix = "",
        private ?bool   $hasTrailingSlash = false,
        private ?string $formatterFunction = null,
        private ?string $locationSpacebarReplaceCharacter = null
    )
    {
        parent::__construct($this->placement, $this->queryParameter, $this->hasTrailingSlash, $this->formatterFunction, $this->locationSpacebarReplaceCharacter);
    }

    /**
     * @return string
     */
    public function getUriPartPrefix(): string
    {
        return $this->uriPartPrefix;
    }

    /**
     * @param string $uriPartPrefix
     */
    public function setUriPartPrefix(string $uriPartPrefix): void
    {
        $this->uriPartPrefix = $uriPartPrefix;
    }

    /**
     * @return string
     */
    public function getNamePlaceholder(): string
    {
        return LocationSearchUriConstants::LOCATION_PLACEHOLDER;
    }

    /**
     * @return string
     */
    public function getNamePrefixPlaceholder(): string
    {
        return LocationSearchUriConstants::LOCATION_PREFIX_PLACEHOLDER;
    }

}