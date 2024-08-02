<?php
namespace JobSearcher\DTO\JobService\SearchConfiguration;

/**
 * Represents the result of reading the yaml file
 */
class ConfigurationReadingResult
{

    public function __construct(
        private string $configurationFilePath,
        private string $supportedCountry,
        private array  $configurationContent
    ){}

    /**
     * @return string
     */
    public function getConfigurationFilePath(): string
    {
        return $this->configurationFilePath;
    }

    /**
     * @return string
     */
    public function getSupportedCountry(): string
    {
        return $this->supportedCountry;
    }

    /**
     * @return array
     */
    public function getConfigurationContent(): array
    {
        return $this->configurationContent;
    }

}