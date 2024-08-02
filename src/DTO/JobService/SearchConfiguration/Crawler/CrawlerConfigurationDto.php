<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Crawler;

/**
 * Crawler configuration for crawling process
 */
class CrawlerConfigurationDto
{
    /**
     * @var CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForPaginationPage
     */
    private CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForPaginationPage;

    /**
     * @var CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForDetailPage
     */
    private CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForDetailPage;

    /**
     * Delay (in milliseconds) between crawling next links,
     * - can be used to prevent baning, captcha etc.
     *
     * @var int|null $crawlDelay
     */
    private ?int $crawlDelay = null;

    /**
     * @return CrawlerPageTypeConfigurationDto
     */
    public function getCrawlerConfigurationDtoForPaginationPage(): CrawlerPageTypeConfigurationDto
    {
        return $this->crawlerConfigurationDtoForPaginationPage;
    }

    /**
     * @param CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForPaginationPage
     */
    public function setCrawlerConfigurationDtoForPaginationPage(CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForPaginationPage): void
    {
        $this->crawlerConfigurationDtoForPaginationPage = $crawlerConfigurationDtoForPaginationPage;
    }

    /**
     * @return CrawlerPageTypeConfigurationDto
     */
    public function getCrawlerConfigurationDtoForDetailPage(): CrawlerPageTypeConfigurationDto
    {
        return $this->crawlerConfigurationDtoForDetailPage;
    }

    /**
     * @param CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForDetailPage
     */
    public function setCrawlerConfigurationDtoForDetailPage(CrawlerPageTypeConfigurationDto $crawlerConfigurationDtoForDetailPage): void
    {
        $this->crawlerConfigurationDtoForDetailPage = $crawlerConfigurationDtoForDetailPage;
    }

    /**
     * @return int|null
     */
    public function getCrawlDelay(): ?int
    {
        return $this->crawlDelay;
    }

    /**
     * @param int|null $crawlDelay
     */
    public function setCrawlDelay(?int $crawlDelay): void
    {
        $this->crawlDelay = $crawlDelay;
    }

}