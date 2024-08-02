<?php

namespace JobSearcher\Service\JobSearch\UrlHandler\General\Location;

use JobSearcher\Service\Url\UrlService;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;
use JobSearcher\Service\JobService\ConfigurationBuilder\Constants\LocationSearchUriConstants;

/**
 * Contains logic responsible strictly for handling location in url
 * Example:
 * - target website: https://de.indeed.com
 * - uri without location: /jobs?sort=date&q=
 * - uri with location: /jobs?sort=date&l=Dresden&q=
 */
class LocationNameUrlHandlerService extends BaseLocationUrlHandlerService
{

    /**
     * Will append the location to the provided string (uri/full-path)
     *
     * @param string                        $uri
     * @param string|null                   $locationName
     * @param BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto
     *
     * @return string
     */
    public function append(string $uri, ?string $locationName, BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto): string
    {
        if (
                empty($locationName)
            ||  $baseSearchUriConfigurationDto->getLocationNameConfiguration()->isNotBeingSetInQuery()
        ) {
            $uri = $this->removePlaceholders($uri);
            return $uri;
        }

        return $this->handle($uri, $locationName, $baseSearchUriConfigurationDto->getLocationNameConfiguration(), $baseSearchUriConfigurationDto);
    }

    /**
     * Will append the location just by adding the location to the uri
     *
     * @return string
     * @throws \Exception
     */
    protected function appendAsUriPart(): string
    {
        $uri = $this->getUri();
        if (str_contains($uri, LocationSearchUriConstants::LOCATION_PLACEHOLDER)) {
            $uri = $this->replacePlaceholders($uri);
        } else {
            $appendedData = $this->handleFormatter($this->getHandledData());
            $appendedData = self::handleSpacebarReplace($this->getBaseSearchUriConfigurationDto()->getLocationNameConfiguration(), $appendedData);
            $uri .= $this->getBaseSearchUriConfigurationDto()->getLocationNameConfiguration()->getUriPartPrefix() . $appendedData;
        }

        if ($this->getBaseLocation()->isTrailingSlash()) {
            return UrlService::appendTrailingSlash($uri);
        }

        return $uri;
    }

    /**
     * Will replace the placeholders with the data based on the input and configuration
     *
     * @param string $uri
     *
     * @return string
     */
    private function replacePlaceholders(string $uri): string
    {
        $appendedData = $this->handleFormatter($this->getHandledData());
        $appendedData = self::handleSpacebarReplace($this->getBaseSearchUriConfigurationDto()->getLocationNameConfiguration(), $appendedData);
        $locationString = $this->getBaseSearchUriConfigurationDto()->getLocationNameConfiguration()->getUriPartPrefix();

        $uri = str_replace(LocationSearchUriConstants::LOCATION_PREFIX_PLACEHOLDER, $locationString, $uri);
        $uri = str_replace(LocationSearchUriConstants::LOCATION_PLACEHOLDER, $appendedData, $uri);

        return $uri;
    }

    /**
     * No location provided - just remove the placeholders
     * This logic will most likely need to be extended in future as there might be cases where both placeholders
     * are replaced with slash and that slash should then get removed as well, should add some configurable:
     * - uriPartsHandlers [], where constants in array would point to the logic that should be used for cleanup
     *
     * @param string $uri
     *
     * @return string
     */
    private function removePlaceholders(string $uri): string
    {
        $uri = str_replace(LocationSearchUriConstants::LOCATION_PREFIX_PLACEHOLDER, "", $uri);
        $uri = str_replace(LocationSearchUriConstants::LOCATION_PLACEHOLDER, "", $uri);

        return $uri;
    }

}