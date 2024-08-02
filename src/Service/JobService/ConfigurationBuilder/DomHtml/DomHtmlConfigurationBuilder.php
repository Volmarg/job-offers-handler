<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder\DomHtml;

use Exception;
use JobSearcher\DTO\JobSearch\DOM\DomElementConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Crawler\CrawlerConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Crawler\CrawlerPageTypeConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\DetailPageConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\SearchUriConfigurationDto;
use JobSearcher\Service\JobService\ConfigurationBuilder\AbstractConfigurationBuilder;
use JobSearcher\Service\JobService\ConfigurationBuilder\Api\ApiConfigurationBuilder;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;

/**
 * Contain all the job search configuration.
 * These are then used for fetching the job offers details by fetching content directly from DOM HTML
 */
class DomHtmlConfigurationBuilder extends AbstractConfigurationBuilder implements DomHtmlConfigurationBuilderInterface
{

    /**
     * {@inheritDoc}
     * @return string
     */
    protected function getConfigurationFilesFolderName(): string
    {
        return "dom";
    }

    /**
     * @var MainConfigurationDto[]
     */
    private array $jobSearchConfigurations = [];

    /**
     * @param string|null $country
     *
     * @return MainConfigurationDto[]
     */
    public function getJobSearchConfigurations(?string $country = null): array
    {
        if (empty($country)) {
            return $this->jobSearchConfigurations;
        }

        $filteredConfigurations = [];
        foreach ($this->jobSearchConfigurations as $jobSearchConfiguration) {
            if ($jobSearchConfiguration->getSupportedCountry() === $country) {
                $filteredConfigurations[$jobSearchConfiguration->getConfigurationName()] = $jobSearchConfiguration;
            }
        }

        return $filteredConfigurations;
    }

    /**
     * Will build array of {@see MainConfigurationDto} and fill the property {@see ApiConfigurationBuilder::$jobSearchConfigurations}
     *
     * @throws Exception
     */
    public function loadAllConfigurations(): void
    {

        $allConfigurationReadingResults = $this->readConfigurationFilesAndValidateTheirContent();
        foreach($allConfigurationReadingResults as $configurationName => $configurationReadingResult){
            $mainConfigurationDTo       = $this->buildMainConfigurationDto($configurationReadingResult->getConfigurationContent(), $configurationName);
            $detailPageConfigurationDto = $this->buildDetailPageConfigurationDto($configurationReadingResult->getConfigurationContent());
            $crawlerConfigurationDto    = $this->setCrawlerConfigurationFromYamlParsedData($configurationReadingResult->getConfigurationContent());
            $searchUriConfigurationDto  = $this->buildSearchUriConfigurationDto($configurationReadingResult->getConfigurationContent());
            $selectorsDtos              = $this->buildSelectorDtos($configurationReadingResult->getConfigurationContent());

            $mainConfigurationDTo->setDetailPageConfigurationDto($detailPageConfigurationDto);
            $mainConfigurationDTo->setSearchUriConfigurationDto($searchUriConfigurationDto);
            $mainConfigurationDTo->setCrawlerConfigurationDto($crawlerConfigurationDto);
            $mainConfigurationDTo->setDomElementsSelectorsAndAttributeConfigurations($selectorsDtos);
            $mainConfigurationDTo->setSupportedCountry($configurationReadingResult->getSupportedCountry());

            $this->jobSearchConfigurations[$configurationName] = $mainConfigurationDTo;
        }

    }

    /**
     * Will use the parsed array and ensure that it's structure is valid
     *
     * @throws Exception
     */
    protected function validateConfiguration(array $parsedFile): void
    {
        # Crawler configuration
        ## General
            $crawlDelay = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile,self::KEY_DOTTED_CRAWLER_CONFIG_CRAWL_DELAY);
            if (!empty($crawlDelay)) {
                $this->validateCrawlingDelay($crawlDelay, $parsedFile);
            }

        ## Pagination pages
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_ENGINE);

        ## Detail pages
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_ENGINE);

        # Selectors
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_SELECTORS);
    }

    /**
     * Will set the crawler configuration
     *
     * @param array $parsingResult
     * @return CrawlerConfigurationDto
     */
    private function setCrawlerConfigurationFromYamlParsedData(array $parsingResult): CrawlerConfigurationDto
    {
        $crawlerConfigurationDto                  = new CrawlerConfigurationDto();
        $crawlerConfigurationDtoForPaginationPage = new CrawlerPageTypeConfigurationDto();
        $crawlerConfigurationDtoForDetailPage     = new CrawlerPageTypeConfigurationDto();

        $paginationPageEngine                        = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_ENGINE);
        $paginationPageWaitMilliseconds              = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_WAIT_MILLISECONDS) ?? null;
        $paginationPageWaitForFunctionToReturnTrue   = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_WAIT_FOR_FUNCTION_TO_RETURN_TRUE) ?? "";
        $paginationPageWaitForDomElementSelectorName = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_WAIT_FOR_DOM_ELEMENT_SELECTOR_NAME) ?? "";
        $paginationPageExtraConfiguration            = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_EXTRA_CONFIG) ?? [];

        $detailPageEngine                        = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_ENGINE);
        $detailPageWaitMilliseconds              = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_WAIT_MILLISECONDS) ?? null;
        $detailPageWaitForFunctionToReturnTrue   = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_WAIT_FOR_FUNCTION_TO_RETURN_TRUE) ?? "";
        $detailPageWaitForDomElementSelectorName = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_WAIT_FOR_DOM_ELEMENT_SELECTOR_NAME) ?? "";
        $detailPageExtraConfiguration            = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_EXTRA_CONFIG) ?? [];

        $crawlDelay = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_CRAWL_DELAY) ?? self::MINIMAL_CRAWL_DELAY;

        $crawlerConfigurationDto->setCrawlDelay($crawlDelay);

        $detailHeaders     = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_DETAILS_HEADERS) ?? [];
        $paginationHeaders = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult,self::KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_HEADERS) ?? [];

        $crawlerConfigurationDtoForPaginationPage->setEngine($paginationPageEngine);
        $crawlerConfigurationDtoForPaginationPage->setWaitMilliseconds($paginationPageWaitMilliseconds);
        $crawlerConfigurationDtoForPaginationPage->setWaitForFunctionToReturnTrue($paginationPageWaitForFunctionToReturnTrue);
        $crawlerConfigurationDtoForPaginationPage->setWaitForDomElementSelectorName($paginationPageWaitForDomElementSelectorName);
        $crawlerConfigurationDtoForPaginationPage->setExtraConfiguration($paginationPageExtraConfiguration);
        $crawlerConfigurationDtoForPaginationPage->setHeaders($paginationHeaders);

        $crawlerConfigurationDtoForDetailPage->setEngine($detailPageEngine);
        $crawlerConfigurationDtoForDetailPage->setWaitMilliseconds($detailPageWaitMilliseconds);
        $crawlerConfigurationDtoForDetailPage->setWaitForFunctionToReturnTrue($detailPageWaitForFunctionToReturnTrue);
        $crawlerConfigurationDtoForDetailPage->setWaitForDomElementSelectorName($detailPageWaitForDomElementSelectorName);
        $crawlerConfigurationDtoForDetailPage->setExtraConfiguration($detailPageExtraConfiguration);
        $crawlerConfigurationDtoForDetailPage->setHeaders($detailHeaders);

        $crawlerConfigurationDto->setCrawlerConfigurationDtoForDetailPage($crawlerConfigurationDtoForDetailPage);
        $crawlerConfigurationDto->setCrawlerConfigurationDtoForPaginationPage($crawlerConfigurationDtoForPaginationPage);

        return $crawlerConfigurationDto;
    }

    /**
     * Will set the {@see SearchUriConfigurationDto}
     *
     * @param array $parsingResult
     * @return SearchUriConfigurationDto
     */
    private function buildSearchUriConfigurationDto(array $parsingResult): SearchUriConfigurationDto
    {
        $baseSearchUriConfigurationDto = $this->buildBaseSearchUriConfigurationDto($parsingResult);
        $searchUriConfigurationDto     = SearchUriConfigurationDto::buildFromBaseDto($baseSearchUriConfigurationDto);

        return $searchUriConfigurationDto;
    }

    /**
     * Will build {@see MainConfigurationDto} from yaml parsed data
     *
     * @param array  $parsingResult
     * @param string $configurationName
     *
     * @return MainConfigurationDto
     */
    private function buildMainConfigurationDto(array $parsingResult, string $configurationName): MainConfigurationDto
    {
        $baseDto              = $this->buildBaseMainConfigurationDto($parsingResult, $configurationName);
        $mainConfigurationDto = MainConfigurationDto::buildFromBaseDto($baseDto);
        return $mainConfigurationDto;
    }

    /**
     * Will set the {@see DetailPageConfigurationDto}
     *
     * @param array $parsingResult
     *
     * @return DetailPageConfigurationDto
     */
    private function buildDetailPageConfigurationDto(array $parsingResult): DetailPageConfigurationDto
    {
        $baseDto                          = $this->buildBaseDetailPageConfigurationDto($parsingResult);
        $searchDetailPageConfigurationDto = DetailPageConfigurationDto::buildFromBaseDto($baseDto);
        return $searchDetailPageConfigurationDto;
    }

    /**
     * Will return array of {@see DomElementConfigurationDto}
     *
     * @param array $parsingResult
     * @return DomElementConfigurationDto[]
     */
    private function buildSelectorDtos(array $parsingResult): array
    {
        $arrayOfDto = [];
        $selectors  = $parsingResult[self::KEY_SELECTORS];
        foreach($selectors as $selector){
            $domElementPurpose         = $selector[self::KEY_DOM_ELEMENT_PURPOSE];
            $cssSelector               = $selector[self::KEY_CSS_SELECTOR];
            $iframeCssSelector         = $selector[self::KEY_CSS_IFRAME_SELECTOR] ?? null;
            $targetAttributeName       = $selector[self::KEY_TARGET_ATTRIBUTE_NAME];
            $getDataFromInnerText      = $selector[self::KEY_GET_DATA_FROM_INNER_TEXT];
            $dataFromInnerTextWithHtml = $selector[self::KEY_DATA_FROM_INNER_TEXT_WITH_HTML] ?? false;
            $getDataFromAttribute      = $selector[self::KEY_GET_DATA_FROM_ATTRIBUTE];
            $calledMethodName          = $selector[self::KEY_CALLED_METHOD_NAME] ?? null;
            $calledMethodArgs          = $selector[self::KEY_CALLED_METHOD_ARGS] ?? [];
            $removedElementsSelectors  = $selector[self::KEY_REMOVED_ELEMENTS_SELECTORS] ?? [];

            $dto = new DomElementConfigurationDto(
                $domElementPurpose,
                $cssSelector,
                $targetAttributeName,
                $getDataFromInnerText,
                $getDataFromAttribute,
                $calledMethodName,
                $dataFromInnerTextWithHtml,
                $removedElementsSelectors,
                $iframeCssSelector,
                $calledMethodArgs
            );

            $arrayOfDto[] = $dto;
        }

        return $arrayOfDto;
    }

}