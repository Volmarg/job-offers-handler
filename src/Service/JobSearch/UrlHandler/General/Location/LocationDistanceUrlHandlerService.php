<?php

namespace JobSearcher\Service\JobSearch\UrlHandler\General\Location;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation\BaseLocation;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation\BaseLocationDistance;
use JobSearcher\Service\Url\UrlService;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;

/**
 * Contains logic responsible strictly for handling location in url
 * Example:
 * - target website: https://de.indeed.com
 * - uri without location: /jobs?sort=date&q=
 * - uri with location: /jobs?sort=date&l=Dresden&q=
 */
class LocationDistanceUrlHandlerService extends BaseLocationUrlHandlerService
{
    /**
     * @var BaseLocationDistance $baseLocationDistance
     */
    private BaseLocationDistance $baseLocationDistance;

    /**
     * Will append the location to the provided string (uri/full-path)
     *
     * @param string                        $uri
     * @param int|null                      $distance
     * @param BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto
     *
     * @return string
     */
    public function append(string $uri, ?int $distance, BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto): string
    {
        if (
            (
                    empty($distance)
                ||  $baseSearchUriConfigurationDto->getLocationDistanceConfiguration()->isNotBeingSetInQuery()
            )
            &&  !$baseSearchUriConfigurationDto->getLocationDistanceConfiguration()->hasRequiredDistance()
        ) {
            return $uri;
        }

        return $this->handle($uri, $distance, $baseSearchUriConfigurationDto->getLocationDistanceConfiguration(), $baseSearchUriConfigurationDto);
    }

    /**
     * {@inheritDoc}
     */
    protected function setProps(
        string                        $uri,
        mixed                         $handledData,
        BaseLocation                  $baseLocationConfiguration,
        BaseSearchUriConfigurationDto $baseSearchUriConfiguration
    ): void {
        parent::setProps($uri, $handledData, $baseLocationConfiguration, $baseSearchUriConfiguration);
        $this->baseLocationDistance = $baseSearchUriConfiguration->getLocationDistanceConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    protected function appendAsUriPart(): string
    {
        $uri = $this->getUri();
        $uri .= $this->getHandledData();

        if ($this->getBaseLocation()->isTrailingSlash()) {
            return UrlService::appendTrailingSlash($uri);
        }

        return $uri;
    }

    /**
     * {@inheritDoc}
     */
    protected function appendAsQueryParameter(): string
    {
        $usedValueKey = $this->baseLocationDistance->getDefaultRequired();
        if (!is_null($this->getHandledData())) {
            $closestValueKey = $this->baseLocationDistance->getClosestDistance($this->getHandledData());
            $usedValueKey    = $closestValueKey ?? $this->getHandledData();
        }

        // no mapping available - just take the provided value then
        $paramAppendCharacter = UrlService::getQueryParamAppendCharacter($this->getUri());

        $uri = $this->getUri()
               . $paramAppendCharacter
               . $this->getBaseLocation()->getQueryParameter()
               . "="
               . $usedValueKey;

        return $uri;
    }

}