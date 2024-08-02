<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base;

/**
 * Represents the main dto which then will contain all the "SUB DTO" all together
 */
class BaseMainConfigurationDto
{
    /**
     * @var string $configurationName
     */
    private string $configurationName;

    /**
     * @var string $host
     */
    private string $host;

    /**
     * @var array $detailPageLinkExcludedPatterns
     */
    private array $detailPageLinkExcludedPatterns;

    /**
     * @var array $detailPageLinkReplaceRegexRules
     */
    private array $detailPageLinkReplaceRegexRules;

    /**
     * @var string $supportedCountry
     */
    private string $supportedCountry;

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return array
     */
    public function getDetailPageLinkExcludedPatterns(): array
    {
        return $this->detailPageLinkExcludedPatterns;
    }

    /**
     * @param array $detailPageLinkExcludedPatterns
     */
    public function setDetailPageLinkExcludedPatterns(array $detailPageLinkExcludedPatterns): void
    {
        $this->detailPageLinkExcludedPatterns = $detailPageLinkExcludedPatterns;
    }

    /**
     * @return array
     */
    public function getDetailPageLinkReplaceRegexRules(): array
    {
        return $this->detailPageLinkReplaceRegexRules;
    }

    /**
     * @param array $detailPageLinkReplaceRegexRules
     */
    public function setDetailPageLinkReplaceRegexRules(array $detailPageLinkReplaceRegexRules): void
    {
        $this->detailPageLinkReplaceRegexRules = $detailPageLinkReplaceRegexRules;
    }

    /**
     * @return string
     */
    public function getConfigurationName(): string
    {
        return $this->configurationName;
    }

    /**
     * @param string $configurationName
     */
    public function setConfigurationName(string $configurationName): void
    {
        $this->configurationName = $configurationName;
    }

    /**
     * Allows building child from {@see BaseMainConfigurationDto}
     *
     * @param BaseMainConfigurationDto $baseMainConfigurationDto
     * @return static
     */
    public static function buildFromBaseDto(BaseMainConfigurationDto $baseMainConfigurationDto): static
    {
        $mainConfigurationDto = new static();
        $mainConfigurationDto->setDetailPageLinkExcludedPatterns($baseMainConfigurationDto->getDetailPageLinkExcludedPatterns());
        $mainConfigurationDto->setDetailPageLinkReplaceRegexRules($baseMainConfigurationDto->getDetailPageLinkReplaceRegexRules());
        $mainConfigurationDto->setHost($baseMainConfigurationDto->getHost());
        $mainConfigurationDto->setConfigurationName($baseMainConfigurationDto->getConfigurationName());

        return $mainConfigurationDto;
    }

    /**
     * @return string
     */
    public function getSupportedCountry(): string
    {
        return $this->supportedCountry;
    }

    /**
     * @param string $supportedCountry
     */
    public function setSupportedCountry(string $supportedCountry): void
    {
        $this->supportedCountry = $supportedCountry;
    }

}