<?php

namespace JobSearcher\Service\JobSearch\UrlHandler\General\Location;

use Exception;
use JobSearcher\Service\Url\UrlService;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation\BaseLocation;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;
use LogicException;

/**
 * Contains common logic for any in-url location based service
 */
abstract class BaseLocationUrlHandlerService
{
    private string $uri;
    private BaseLocation $baseLocation;
    private BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto;

    // be it location / distance etc. - anything related to the location that is handled in context of parent class
    private ?string $handledData;

    /**
     * @return BaseLocation
     */
    public function getBaseLocation(): BaseLocation
    {
        return $this->baseLocation;
    }

    /**
     * @return string|null
     */
    public function getHandledData(): ?string
    {
        return $this->handledData;
    }

    /**
     * @return BaseSearchUriConfigurationDto
     */
    public function getBaseSearchUriConfigurationDto(): BaseSearchUriConfigurationDto
    {
        return $this->baseSearchUriConfigurationDto;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Will append the location just by adding the location to the uri
     *
     * @return string
     */
    abstract protected function appendAsUriPart(): string;

    /**
     * @param BaseLocation $baseLocation
     * @param string|null  $data
     *
     * @return string|null
     */
    public static function handleSpacebarReplace(BaseLocation $baseLocation, ?string $data = null): ?string
    {
        // must be explicit check for "empty" on data, because it could happen that location is empty, and it would become just a single character
        if (empty($data) || is_null($baseLocation->getLocationSpacebarReplaceCharacter())) {
            return $data;
        }

        return str_replace(" ", $baseLocation->getLocationSpacebarReplaceCharacter(), $data);
    }

    /**
     * Will append the location to the provided string (uri/full-path)
     *
     * @param string                        $uri
     * @param mixed                         $handledData
     * @param BaseLocation                  $baseLocationConfiguration
     * @param BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto
     *
     * @return string
     */
    protected function handle(string $uri, mixed $handledData, BaseLocation $baseLocationConfiguration, BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto): string
    {
        $baseLocationConfiguration->validateSelf();
        $this->setProps($uri, $handledData, $baseLocationConfiguration, $baseSearchUriConfigurationDto);
        return $this->decideAppendingMethod();
    }

    /**
     * Sets the class props
     *
     * @param string                        $uri
     * @param mixed                         $handledData
     * @param BaseLocation                  $baseLocationConfiguration
     * @param BaseSearchUriConfigurationDto $baseSearchUriConfiguration
     *
     * @return void
     */
    protected function setProps(string $uri, mixed $handledData, BaseLocation $baseLocationConfiguration, BaseSearchUriConfigurationDto $baseSearchUriConfiguration): void
    {
        if (
                !is_scalar($handledData)
            &&  !is_null($handledData)
        ) {
            $type  = gettype($handledData);
            $value = json_encode($handledData, JSON_PRETTY_PRINT);
            throw new LogicException("Only scalars are allowed as data for handling! Got data of type: {$type}, value: {$value}");
        }

        $this->uri                           = $uri;
        $this->handledData                   = $handledData;
        $this->baseLocation                  = $baseLocationConfiguration;
        $this->baseSearchUriConfigurationDto = $baseSearchUriConfiguration;
    }

    /**
     * Will append the location based data as query parameter
     */
    protected function appendAsQueryParameter(): string
    {
        $appendedLocation     = $this->handleFormatter($this->getHandledData());
        $appendedLocation     = self::handleSpacebarReplace($this->getBaseLocation(), $appendedLocation);
        $paramAppendCharacter = UrlService::getQueryParamAppendCharacter($this->getUri());
        $uri = $this->getUri()
               . $paramAppendCharacter
               . $this->getBaseLocation()->getQueryParameter()
               . "="
               . $appendedLocation;

        return $uri;
    }

    /**
     * Will decide how the appending should happen and will perform it
     */
    private function decideAppendingMethod(): string
    {
        if ($this->baseLocation->isQueryParamBased()) {
            return $this->appendAsQueryParameter();
        }

        if ($this->baseLocation->isUriPartBased()) {
            return $this->appendAsUriPart();
        }

        throw new LogicException("It's unknown how the location data should be appended to the uri, configuration is wrong!");
    }

    /**
     * If name of the formatter function is set then this function will be called (if exists),
     * and will modify the provided data. If method does not exist or data is not set then the original data
     * gets returned
     *
     * @param string|null $data
     *
     * @return string|null
     * @throws Exception
     */
    protected function handleFormatter(?string $data = null): ?string
    {
        if (empty($data) || empty($this->baseLocation->getFormatterFunction())) {
            return $data;
        }

        if (!function_exists($this->baseLocation->getFormatterFunction())) {
            throw new Exception("Location formatter function does not exist, tried calling: " . $this->baseLocation->getFormatterFunction());
        }

        $handledData = $this->getHandledData();
        if (!empty($this->getHandledData())) {
            $functionName = $this->baseLocation->getFormatterFunction();
            $handledData = $functionName($this->getHandledData());
        }

        return $handledData;
    }
}