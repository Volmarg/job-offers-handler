<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation;

use JobSearcher\Service\JobService\ConfigurationBuilder\Constants\LocationSearchUriConstants;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;

/**
 * Handles the location based search in uri
 * Related to {@see BaseLocationName}
 */
class BaseLocationDistance extends BaseLocation
{
    public function __construct(
        private ?string $placement = null,
        private ?string $queryParameter = null,
        private ?bool   $hasTrailingSlash = false,
        private array   $allowedDistances = [],
        private ?int    $defaultRequired  = null
    )
    {
        parent::__construct($this->placement, $this->queryParameter, $this->hasTrailingSlash);
    }

    /**
     * @return array
     */
    public function getAllowedDistances(): array
    {
        return $this->allowedDistances;
    }

    /**
     * @param array $allowedDistances
     */
    public function setAllowedDistances(array $allowedDistances): void
    {
        $this->allowedDistances = $allowedDistances;
    }

    /**
     * @return int|null
     */
    public function getDefaultRequired(): ?int
    {
        return $this->defaultRequired;
    }

    /**
     * @param int|null $defaultRequired
     */
    public function setDefaultRequired(?int $defaultRequired): void
    {
        $this->defaultRequired = $defaultRequired;
    }

    /**
     * @return bool
     */
    public function hasRequiredDistance(): bool
    {
        return !is_null($this->getDefaultRequired());
    }

    /**
     * @return string
     */
    public function getDistancePlaceholder(): string
    {
        return LocationSearchUriConstants::DISTANCE_PLACEHOLDER;
    }

    /**
     * Will try to obtain closest `highest` distance that can be used in call
     * - if fails, then will try to obtain closest lowest,
     * - if that still fails then null is returned
     *
     * Keep in mind that the returned value is KEY from mapping,
     * the KEY is to be used in request, and might not represent the real distance
     * - that depends on services, if they decided that `4` is equal to `10km`
     *
     * @param int $distance
     *
     * @return int|null
     */
    public function getClosestDistance(int $distance): ?int
    {
        return ArrayTypeProcessor::getKeyForClosestNumber($distance, $this->getAllowedDistances());
    }

}