<?php

namespace JobSearcher\Service\JobSearch\Scrapper\DomHtml;

use BadFunctionCallException;
use Exception;
use JobSearcher\DTO\JobSearch\DOM\DomElementConfigurationDto;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BasePaginationOfferDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;
use JobSearcher\Service\DOM\DomContentReducerService;
use JobSearcher\Service\Finance\MoneyParserService;
use JobSearcher\Service\JobSearch\Crawler\DomHtml\IframeCrawlerService;
use JobSearcher\Service\JobSearch\Scrapper\BaseScrapperService;
use Symfony\Component\DomCrawler\Crawler;
use TypeError;

/**
 * Handles scrapping data from provided search result (crawler / raw string etc.)
 */
class ScrapperService extends BaseScrapperService
{
    /**
     * @var MainConfigurationDto $mainConfigurationDto
     */
    private MainConfigurationDto $mainConfigurationDto;

    /**
     * @return MainConfigurationDto
     */
    public function getMainConfigurationDto(): MainConfigurationDto
    {
        return $this->mainConfigurationDto;
    }

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     */
    public function setMainConfigurationDto(MainConfigurationDto $mainConfigurationDto): void
    {
        $this->mainConfigurationDto = $mainConfigurationDto;
    }

    public function __construct(
        private readonly MoneyParserService   $moneyParserService,
        private readonly IframeCrawlerService $iframeCrawlerService
    ){}

    /**
     * Will extract data from content by using {@see Crawler} and configuration set in {@see DomElementConfigurationDto}
     *
     * @param Crawler $crawler
     * @param string  $domElementConfigurationPurpose
     *
     * @return array
     * @throws Exception
     */
    public function scrapDataWithCrawlerAndDomConfiguration(
        Crawler $crawler,
        string $domElementConfigurationPurpose
    ): array {
        /**
         * For given purposes html value will be extracted instead of plain text,
         * (which causes issues with not adding any spacebars in place of removed tags), so it ends up
         * with words being glued together etc.
         */
        $htmlValueExtractionPurposes = [
            DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_CONTACT_DETAIL_COMPANY_PHONE,
            DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_CONTACT_DETAIL_COMPANY_EMAIL,
            DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_OFFER_DETAIL_JOB_DESCRIPTION,
        ];

        $allMatchingValues = [];
        $domElementConfig  = $this->mainConfigurationDto->getDomElementSelectorAndAttributeConfiguration($domElementConfigurationPurpose);

        if (empty($domElementConfig->getCssSelector())) {
            return [];
        }

        if ($domElementConfig->getIframeCssSelector()) {
            $currentCrawler = $crawler;
            $crawler        = $this->iframeCrawlerService->buildCrawlerFromIframe($domElementConfig->getIframeCssSelector(), $currentCrawler);
        }

        foreach ($crawler->filter($domElementConfig->getCssSelector()) as $node) {

            $value = (!empty($node->textContent) ? $node->textContent : "");
            if (
                    in_array($domElementConfigurationPurpose, $htmlValueExtractionPurposes)
                ||  $domElementConfig->isDataFromInnerTextWithHtml()
            ) {
                $htmlProvidingCrawler = new Crawler($node); // this is necessary to get the html from the node, else only text / children are available
                $htmlProvidingCrawler = DomContentReducerService::handleDomNodeReducing($htmlProvidingCrawler, $domElementConfig->getRemovedElementsSelectors());

                $value = (!empty($htmlProvidingCrawler->html()) ? $htmlProvidingCrawler->html() : "");
            }

            if($domElementConfig->isGetDataFromAttribute()){
                $value = $node->attributes->getNamedItem($domElementConfig->getTargetAttributeName())->nodeValue;
            }

            if(
                    !empty($value)
                &&  !is_null($domElementConfig->getCalledMethodName())
            ){
                $processedValue = self::callAllowedMethod(
                    $domElementConfig->getCalledMethodName(), [
                        $value,
                        $this->mainConfigurationDto,
                        ...$domElementConfig->getCalledMethodArgs() // unpack is important here
                    ]
                );
                $allMatchingValues[] = ( !empty($processedValue) ? $processedValue : "" );
                continue;
            }

            $allMatchingValues[] = $value;
        }

        return $allMatchingValues;
    }

    /**
     * {@see ExtractorService::scrapDataWithCrawlerAndDomConfiguration()}
     * However it will return only the first found matching on the page!
     *
     * @param Crawler $crawler
     * @param string  $domElementConfigurationPurpose
     *
     * @return mixed - because {@see ExtractorService::scrapDataWithCrawlerAndDomConfiguration} calls the {@see AbstractExtractor::scrapDataWithCrawler}
     *                 which returns different data types
     * @throws Exception
     */
    public function scrapDataWithCrawlerAndGetFirstMatch(
        Crawler $crawler,
        string $domElementConfigurationPurpose
    ): mixed {

        $allMatches = $this->scrapDataWithCrawlerAndDomConfiguration($crawler, $domElementConfigurationPurpose);
        if( empty($allMatches) ){
            return "";
        }

        return $allMatches[array_key_first($allMatches)];
    }

    /**
     * Will scrap salary information with crawler
     *
     * @param Crawler $crawler
     * @param string $domElementConfigurationReason
     * @return int
     * @throws Exception
     */
    public function scrapSalaryWithCrawler(
        Crawler $crawler,
        string $domElementConfigurationReason
    ): int {
        $stringValue = $this->scrapDataWithCrawlerAndGetFirstMatch($crawler, $domElementConfigurationReason);
        if( empty($stringValue) ){
            return 0;
        }

        $money = $this->moneyParserService->extractMoneyFromString($stringValue);
        return (int)$money;
    }

    /**
     * Will return array of {@see BasePaginationOfferDto} for found job offers
     *
     * @param Crawler               $crawler
     * @param JobSearchParameterBag $searchParams
     *
     * @return BasePaginationOfferDto[]
     * @throws Exception
     */
    public function scrapPaginationPageBlocks(Crawler $crawler, JobSearchParameterBag $searchParams): array
    {
        $paginationScrapperService = new PaginationScrapperService();
        $paginationScrapperService->setMainConfigurationDto($this->getMainConfigurationDto());
        $paginationScrapperService->setScrapperService($this);

        $filteredArrayDto = $paginationScrapperService->scrap($crawler, $searchParams);

        return $filteredArrayDto;
    }

    /**
     * Will call a method by its name and will use params as a called function context,
     *
     * @param string $methodName
     * @param array  $methodParams
     *
     * @return mixed
     * @throws Exception
     */
    public static function callAllowedMethod(string $methodName, array $methodParams): mixed
    {

        if (!in_array($methodName, ScrapperInterface::ALLOWED_METHODS)) {
            $allowedMethodsString = implode(",", ScrapperInterface::ALLOWED_METHODS);
            $message              = "Tried to call a function named: {$methodName}, but it's not allowed to call it. Allowed are: {$allowedMethodsString}!";

            throw new BadFunctionCallException($message);
        }

        try {
            $result = call_user_func([BaseScrapperService::class, $methodName], ...$methodParams);
        } catch (TypeError|Exception $e) {
            $newMsg = "Something went wrong while calling function: " . __CLASS__ . '::' . __FUNCTION__
                  . " | Data " . json_encode([
                    "calledMethod" => $methodName,
                    "methodParam"  => $methodParams,
                ]);

            $msg = "Original: msg: {$e->getMessage()}, trace: {$e->getTrace()}. New msg: {$newMsg}";

            throw new Exception($msg);
        }

        return $result;
    }

}