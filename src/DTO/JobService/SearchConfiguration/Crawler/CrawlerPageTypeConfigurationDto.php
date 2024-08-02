<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Crawler;

/**
 * Crawler configuration for given type of page
 */
class CrawlerPageTypeConfigurationDto
{
    /**
     * @var string $engine
     */
    private string $engine = "";

    /**
     * @var string $waitForDomElementSelectorName
     */
    private string $waitForDomElementSelectorName = "";

    /**
     * @var string $waitForFunctionToReturnTrue
     */
    private string $waitForFunctionToReturnTrue = "";

    /**
     * @var int|null $waitMilliseconds
     */
    private ?int $waitMilliseconds = null;

    /**
     * @var array $extraConfiguration
     */
    private array $extraConfiguration = [];

    /**
     * @var array $headers
     */
    private array $headers = [];

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * @param string $engine
     */
    public function setEngine(string $engine): void
    {
        $this->engine = $engine;
    }

    /**
     * @return string
     */
    public function getWaitForDomElementSelectorName(): string
    {
        return $this->waitForDomElementSelectorName;
    }

    /**
     * @param string $waitForDomElementSelectorName
     */
    public function setWaitForDomElementSelectorName(string $waitForDomElementSelectorName): void
    {
        $this->waitForDomElementSelectorName = $waitForDomElementSelectorName;
    }

    /**
     * @return string
     */
    public function getWaitForFunctionToReturnTrue(): string
    {
        return $this->waitForFunctionToReturnTrue;
    }

    /**
     * @param string $waitForFunctionToReturnTrue
     */
    public function setWaitForFunctionToReturnTrue(string $waitForFunctionToReturnTrue): void
    {
        $this->waitForFunctionToReturnTrue = $waitForFunctionToReturnTrue;
    }

    /**
     * @return int|null
     */
    public function getWaitMilliseconds(): ?int
    {
        return $this->waitMilliseconds;
    }

    /**
     * @param int|null $waitMilliseconds
     */
    public function setWaitMilliseconds(?int $waitMilliseconds): void
    {
        $this->waitMilliseconds = $waitMilliseconds;
    }

    /**
     * @return array
     */
    public function getExtraConfiguration(): array
    {
        return $this->extraConfiguration;
    }

    /**
     * @param array $extraConfiguration
     */
    public function setExtraConfiguration(array $extraConfiguration): void
    {
        $this->extraConfiguration = $extraConfiguration;
    }

}