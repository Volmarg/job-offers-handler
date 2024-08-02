<?php

namespace JobSearcher\Service\JobSearch\Extractor\DomHtml;

use Exception;
use JobSearcher\Constants\JobOfferService\GermanJobOfferService;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\DTO\JobService\CrawlerWithPaginationResultDto;
use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BasePaginationOfferDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Exception\Bundle\ProxyProvider\ExternalProxyNotReachableException;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Service\Bundle\ProxyProvider\ProxyProviderService;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;
use JobSearcher\Service\JobSearch\Crawler\DynamicDelayDecider;
use JobSearcher\Service\JobSearch\Decider\OfferSavingDecider;
use JobSearcher\Service\JobSearch\Extractor\AbstractExtractor;
use JobSearcher\Service\JobSearch\ResultBuilder\DomHtml\ResultBuilderService;
use JobSearcher\Service\JobSearch\Scrapper\DomHtml\ScrapperService;
use JobSearcher\Service\JobSearch\UrlHandler\DomHtml\UrlHandlerService;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;

/**
 * This services handles extracting the job data for given uri out of DOM itself.
 * It handles the complete process of going to detail pages getting salaries / company data etc.
 */
class ExtractorService extends AbstractExtractor
{
    /**
     * @var MainConfigurationDto $mainConfigurationDto
     */
    private MainConfigurationDto $mainConfigurationDto;

    /**
     * @var CrawlerService $crawlerService
     */
    private CrawlerService $crawlerService;

    /**
     * @var UrlHandlerService $urlHandlerService
     */
    private UrlHandlerService $urlHandlerService;

    /**
     * @var JobSearchResultRepository $jobSearchResultRepository
     */
    private JobSearchResultRepository $jobSearchResultRepository;

    private bool $hasResultsForFirstPaginationPage = true;

    private readonly int $maxDaysOffsetBeforeCreatingNewerDuplicate;

    private ScrapperService $scrapperService;
    private ResultBuilderService $resultBuilderService;
    private string $tempFolder;
    private readonly LoggerInterface $offerExtractionLogger;
    private readonly DynamicDelayDecider $dynamicDelayDecider;
    private readonly OfferSavingDecider $offerSavingDecider;
    private readonly ProxyProviderService $proxyProviderService;

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     * @param KernelInterface      $kernel
     */
    public function __construct(
        MainConfigurationDto             $mainConfigurationDto,
        private readonly KernelInterface $kernel,
    )
    {
        $this->jobSearchResultRepository = $kernel->getContainer()->get(JobSearchResultRepository::class);
        $this->scrapperService           = $kernel->getContainer()->get(ScrapperService::class);
        $this->resultBuilderService      = $kernel->getContainer()->get(ResultBuilderService::class);
        $this->crawlerService            = $kernel->getContainer()->get(CrawlerService::class);
        $this->offerExtractionLogger     = $kernel->getContainer()->get('monolog.logger.offerExtraction');
        $this->dynamicDelayDecider       = $kernel->getContainer()->get(DynamicDelayDecider::class);
        $this->offerSavingDecider        = $kernel->getContainer()->get(OfferSavingDecider::class);
        $this->proxyProviderService      = $kernel->getContainer()->get(ProxyProviderService::class);

        $parameterBag = $kernel->getContainer()->get('parameter_bag_public');

        $this->maxDaysOffsetBeforeCreatingNewerDuplicate = $parameterBag->get("max_days_offset_before_creating_newer_duplicate");
        $this->tempFolder                                = $parameterBag->get("folder.tmp");

        $this->urlHandlerService    = new UrlHandlerService($mainConfigurationDto, $kernel);
        $this->mainConfigurationDto = $mainConfigurationDto;

        $this->scrapperService->setMainConfigurationDto($mainConfigurationDto);
        $this->resultBuilderService->setMainConfigurationDto($mainConfigurationDto);
    }

    /**
     * Will return array of job offers search results for given pagination uri
     *
     * @param JobSearchParameterBag $searchParams
     * @param string                $paginationUri
     * @param int                   $pageNumber
     * @param int|null              $crawlDelay - in milliseconds
     *
     * @return NewAndExistingOffersDto
     * @throws Exception
     */
    public function getSearchResultsForPaginationUrl(JobSearchParameterBag $searchParams, string $paginationUri, int $pageNumber, ?int $crawlDelay = null): NewAndExistingOffersDto
    {
        $this->proxyProviderService->checkProxyReachability();
        $newAndExistingOffersDto = new NewAndExistingOffersDto();

        // if first page has no offers on it, then it's no use to look on other pages at all
        if (!$this->hasResultsForFirstPaginationPage) {
            return $newAndExistingOffersDto;
        }

        $crawlerConfigurationDto = new CrawlerConfigurationDto(
            $paginationUri,
            $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForPaginationPage()->getEngine(),
            $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForPaginationPage()->getWaitForDomElementSelectorName(),
            $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForPaginationPage()->getWaitForFunctionToReturnTrue(),
            $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForPaginationPage()->getWaitMilliseconds(),
            $crawlDelay
        );

        $crawlerConfigurationDto->setHeaders($this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForPaginationPage()->getHeaders());
        $crawlerConfigurationDto->setExtraConfig($this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForPaginationPage()->getExtraConfiguration());
        $crawlerConfigurationDto->setWithProxy(EnvReader::isProxyEnabled());

        $crawler                 = $this->crawlerService->crawl($crawlerConfigurationDto);
        $basePaginationOfferDtos = $this->scrapperService->scrapPaginationPageBlocks($crawler, $searchParams);

        $isFirstPageOk = $this->handleFirstPageMissingOffers($pageNumber, $crawler, $paginationUri, $basePaginationOfferDtos);
        if (!$isFirstPageOk) {
            return $newAndExistingOffersDto;
        }

        $existingEntityIds       = [];
        $basePaginationOfferDtos = $this->urlHandlerService->buildPaginationOfferDtos($basePaginationOfferDtos);
        $filteredPaginationOffers = $this->filterScrappedDetailPageLinks($basePaginationOfferDtos, $existingEntityIds);
        if (!empty($basePaginationOfferDtos) && empty($filteredPaginationOffers)) {
            $this->offerExtractionLogger->info("Found some offers on pagination page, but all were filtered out. Uri: {$paginationUri}");
        }

        $crawlerWithPaginationResultDto = $this->getCrawlersForDetailPageLinks($filteredPaginationOffers);
        $allJobOffers                   = $this->buildSearchResults($crawlerWithPaginationResultDto, $searchParams);
        $existingOffers                 = $this->jobSearchResultRepository->findBy(["id" => $existingEntityIds]);

        $newAndExistingOffersDto->setAllSearchResultDtos($allJobOffers);
        $newAndExistingOffersDto->setExistingOfferEntities($existingOffers);

        return $newAndExistingOffersDto;
    }

    /**
     * Will take a look on results obtained from first page.
     * This helps for example stopping further search for offers (there is no sense to look for offers on page 2...3...4 if first one has no offers)
     *
     * If everything is ok then returns TRUE, else returns FALSE
     *
     * @param int     $pageNumber
     * @param Crawler $crawler
     * @param string  $paginationUri
     * @param array   $basePaginationOfferDtos
     *
     * @return bool
     */
    private function handleFirstPageMissingOffers(int $pageNumber, Crawler $crawler, string $paginationUri, array $basePaginationOfferDtos): bool {

        if (
                $pageNumber === 1
            &&  empty($basePaginationOfferDtos)
        ) {
            $uniqueName          = uniqid("no-results-on-pagination-page");
            $pageContentFilePath = $this->tempFolder . DIRECTORY_SEPARATOR . $uniqueName;
            $insertedContent     = "";
            try {
                $insertedContent = $crawler->html();
            } catch (Exception) {
                try {
                    $insertedContent = $crawler->text();
                } catch (Exception) {
                }
            }

            file_put_contents($pageContentFilePath, $insertedContent);

            $extraData = [];
            if (empty($insertedContent)) {
                $extraData["extraInfo"] = "Tried to get data both with ->html(), and text(), but both resulted in errors or some returned just blank string";
            }

            $this->hasResultsForFirstPaginationPage = false;
            $this->offerExtractionLogger->emergency("There is something wrong with getting offers information from pagination", [
                "details"             => "No offers were found on first page. Not searching on next pages as it makes no sense,",
                "hints"               => [
                    "Maybe the page / dom changed and the configuration for this job services is no longer valid",
                ],
                "url"                 => $paginationUri,
                'pageContentFilePath' => $pageContentFilePath,
                "extractionSource"    => ExtractorInterface::EXTRACTION_SOURCE_DOM,
                "configurationName"   => $this->mainConfigurationDto->getConfigurationName(),
                "shortNote"           => "Got no offers from Pagination",
                ...$extraData
            ]);

            return false;
        }

        return true;
    }

    /**
     * Will return instances of crawlers for each detail page link
     * - crawlers instances can then be used to extract further data from page
     *
     * @param BasePaginationOfferDto[] $basePaginationOfferDtos
     * @return CrawlerWithPaginationResultDto[]
     * @throws Exception
     */
    private function getCrawlersForDetailPageLinks(array $basePaginationOfferDtos): array
    {
        $results    = [];
        $crawlDelay = null; // first call does not require delay
        foreach ($basePaginationOfferDtos as $dto) {
            if ($dto->isExcludedFromScrapping()) {
                continue;
            }

            try {
                $this->proxyProviderService->checkProxyReachability();
                $crawlerConfigurationDto = new CrawlerConfigurationDto(
                    $dto->getAbsoluteJobOfferUrl(),
                    $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForDetailPage()->getEngine(),
                    $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForDetailPage()->getWaitForDomElementSelectorName(),
                    $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForDetailPage()->getWaitForFunctionToReturnTrue(),
                    $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForDetailPage()->getWaitMilliseconds(),
                    $crawlDelay,
                );

                $crawlerConfigurationDto->setHeaders($this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForDetailPage()->getHeaders());
                $crawlerConfigurationDto->setExtraConfig($this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlerConfigurationDtoForDetailPage()->getExtraConfiguration());
                $crawlerConfigurationDto->setWithProxy(EnvReader::isProxyEnabled());

                $crawler     = $this->crawlerService->crawl($crawlerConfigurationDto);
                $crawlDelay  = $this->mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlDelay();
                $crawlDelay += $this->dynamicDelayDecider->decide();

                $results[] = new CrawlerWithPaginationResultDto($crawler, $dto);
            } catch (ExternalProxyNotReachableException $epn) {
                throw $epn;
            } catch (Exception|TypeError $e) {
                $this->offerExtractionLogger->warning("Something went wrong while trying to get crawler for detail page", [
                    "extractionSource"  => ExtractorInterface::EXTRACTION_SOURCE_DOM,
                    "configurationName" => $this->mainConfigurationDto->getConfigurationName(),
                    "shortNote"         => "Issue getting crawler for offer detail page",
                    'offerUrl'  => $dto->getAbsoluteJobOfferUrl(),
                    'exception' => [
                        "class"   => $e::class,
                        "message" => $e->getMessage(),
                        "trace"   => $e->getTraceAsString(),
                    ]
                ]);
            }
        }

        return $results;
    }

    /**
     * Will check if offer was already scrapped:
     * - given job offer url was already scrapped (is in database),
     * - if combination of "job title" and "company name" is already present in DB,
     *
     * This helps in terms of optimization as link doesn't need to crawled again
     *
     * @param BasePaginationOfferDto[] $paginationOffersDto
     * @param                          $existingEntityIds
     *
     * @return array
     */
    protected function filterScrappedDetailPageLinks(array $paginationOffersDto, &$existingEntityIds): array
    {
        $createdAfterDate = (new \DateTime())->modify("-{$this->maxDaysOffsetBeforeCreatingNewerDuplicate} DAYS");

        $absoluteLinks = array_map( fn(BasePaginationOfferDto $dto) => $dto->getAbsoluteJobOfferUrl(), $paginationOffersDto);
        $companyHashes = array_map(
            fn(BasePaginationOfferDto $dto) => md5($dto->getCompanyName() . $dto->getJobOfferTitle()),
            $paginationOffersDto
        );

        $entityIdsForHashes   = $this->jobSearchResultRepository->getIdsForCompanyNameAndJobTitleHashes($companyHashes, $createdAfterDate);
        $existingOfferIdsUrls = $this->jobSearchResultRepository->getExistingOfferIdsForUrls($absoluteLinks, $createdAfterDate);
        $existingEntityIds    = array_unique(array_merge(
            array_keys($entityIdsForHashes),
            array_keys($existingOfferIdsUrls)
        ));

        if (empty($existingEntityIds)) {
            return $paginationOffersDto;
        }

        $filteredDtos = [];
        foreach($paginationOffersDto as $dto) {
            foreach ($existingOfferIdsUrls as $offerUrl) {
                if ($offerUrl === $dto->getAbsoluteJobOfferUrl()) {
                    continue 2;
                }
            }

            $companyHash = md5($dto->getCompanyName() . $dto->getJobOfferTitle());
            foreach ($entityIdsForHashes as $matchingHash) {
                if ($matchingHash === $companyHash) {
                    continue 2;
                }
            }

            $filteredDtos[] = $dto;
        }

        return $filteredDtos;
    }

    /**
     * @param CrawlerWithPaginationResultDto[] $crawlerWithPaginationResultDtos
     *
     * @return SearchResultDto[]
     */
    private function buildSearchResults(array $crawlerWithPaginationResultDtos, JobSearchParameterBag $searchParams): array
    {
        $allJobOffers = [];
        foreach ($crawlerWithPaginationResultDtos as $crawlerWithPaginationResultDto) {
            try {
                $crawler              = $crawlerWithPaginationResultDto->getCrawler();
                $jobOfferSearchResult = $this->resultBuilderService->build($crawlerWithPaginationResultDto, $searchParams);

                if (!$this->offerSavingDecider->canSave($jobOfferSearchResult, $searchParams)) {
                    continue;
                }

                $allJobOffers[] = $jobOfferSearchResult;
            } catch (Exception|TypeError $e) {

                $loggerLevel = Logger::CRITICAL;

                // due to known scrapping / crawling issues
                if ($this->mainConfigurationDto->getConfigurationName() === GermanJobOfferService::JOBWARE_DE) {
                    $loggerLevel = Logger::DEBUG;
                }

                $this->offerExtractionLogger->log($loggerLevel, "Could not handle job offer, skipping it and going to next", [
                    "extractionSource"  => ExtractorInterface::EXTRACTION_SOURCE_DOM,
                    "configurationName" => $this->mainConfigurationDto->getConfigurationName(),
                    "shortNote"         => "Issue handling job offer",
                    "url"               => $crawler?->getUri(),
                    "baseHref"          => $crawler?->getBaseHref(),
                    "exception" => [
                        "message" => $e->getMessage(),
                        "type"    => get_class($e),
                        "trace"   => $e->getTraceAsString(),
                    ]
                ]);
            }
        }

        return $allJobOffers;
    }

}