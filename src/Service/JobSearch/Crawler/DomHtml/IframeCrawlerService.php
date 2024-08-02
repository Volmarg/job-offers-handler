<?php

namespace JobSearcher\Service\JobSearch\Crawler\DomHtml;

use Exception;
use JobSearcher\Exception\Crawler\IframeCrawlerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use WebScrapperBundle\Constants\UserAgentConstants;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;

/**
 * Handles crawling the iframe
 */
class IframeCrawlerService
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CrawlerService  $crawlerService
    ){}

    /**
     * Some services are setting offer content as iframe, this method purpose is to:
     * - extract the url of iframe,
     * - get content of the url iframe via {@see CrawlerService}
     * - return the crawler to be used later on
     *
     * @param string $iframeCssSelector
     * @param Crawler $currentCrawler
     *
     * @return Crawler
     *
     * @throws IframeCrawlerException
     * @throws Exception
     */
    public function buildCrawlerFromIframe(string $iframeCssSelector, Crawler $currentCrawler): Crawler
    {
        $this->logger->debug("Trying to build crawler from iframe on page: {$currentCrawler->getUri()}");

        $noIframeErrorMessage = "
            Got iframe selector but probably no IFRAME node was found on page: {$currentCrawler->getUri()}, 
            for selector: {$iframeCssSelector}
        ";

        $hasIframeNode = false;
        $iframeUrl = null;
        foreach ($currentCrawler->filter($iframeCssSelector) as $node) {
            $hasIframeNode = true;
            $iframeUrl     = $node->attributes->getNamedItem("src")->nodeValue;
        }

        if (
                $hasIframeNode
            &&  empty($iframeUrl)
        ) {
            throw new IframeCrawlerException("Got iframe, but could not extract the source url from `src` attribute. Page: {$currentCrawler->getUri()}");
        }

        if (!$hasIframeNode) {
            throw new IframeCrawlerException($noIframeErrorMessage);
        }

        $usedIframeUrl = $this->buildCrawlAbleIframeUrl($iframeUrl, $currentCrawler);

        // headless is preferred as it's unknown what type of rendering is the external service using
        $crawlerConfig = new CrawlerConfigurationDto($usedIframeUrl, CrawlerService::SCRAP_ENGINE_HEADLESS);
        $crawlerConfig->setUserAgent(UserAgentConstants::CHROME_101);

        $crawler = $this->crawlerService->crawl($crawlerConfig);

        return $crawler;
    }

    /**
     * Prepares Iframe url to be crawled
     *
     * @param string $iframeUrl
     * @param Crawler $currentCrawler
     *
     * @return string
     */
    private function buildCrawlAbleIframeUrl(string $iframeUrl, Crawler $currentCrawler): string
    {
        if (
                !str_starts_with($iframeUrl, "/")
            ||  str_starts_with($iframeUrl, "//")
        ) {
            return $iframeUrl;
        }

        // same host - relative url
        $host   = parse_url($currentCrawler->getUri(), PHP_URL_HOST);
        $scheme = parse_url($currentCrawler->getUri(), PHP_URL_SCHEME);

        $modifiedUrl = "";
        if(!empty($scheme)){
            $modifiedUrl .= $scheme . "://";
        }
        $modifiedUrl .= $host . $iframeUrl;

        return $modifiedUrl;
    }
}