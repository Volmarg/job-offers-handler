<?php

namespace JobSearcher\Service\JobSearch\Command\Extractor\DomHtml;

use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Exception\Bundle\ProxyProvider\ExternalProxyNotReachableException;
use JobSearcher\Exception\JobServiceCallableResolverException;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;
use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Service\JobSearch\Crawler\DynamicDelayDecider;
use JobSearcher\Service\JobSearch\Extractor\DomHtml\ExtractorService                        as ExtractorServiceDomHtml;
use JobSearcher\Service\JobSearch\OfferExtractionLimiterService;
use JobSearcher\Service\JobSearch\UrlHandler\DomHtml\UrlHandlerService                      as UrlHandlerServiceDomHtml;
use JobSearcher\Service\JobService\ConfigurationBuilder\DomHtml\DomHtmlConfigurationBuilder as JobSearchConfigurationDomHtml;
use Exception;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Handles extracting job search offers for given source types,
 * For example:
 * - {@see ExtractorServiceDomHtml}
 */
class ExtractorService implements ExtractorInterface
{

    /**
     * @var JobSearchConfigurationDomHtml $jobSearchConfiguration
     */
    private JobSearchConfigurationDomHtml $jobSearchConfigurationDomHtml;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var ConfigurationReader $configurationReader
     */
    private readonly ConfigurationReader $configurationReader;
    private readonly DynamicDelayDecider $dynamicDelayDecider;

    /**
     * {@inheritDoc}
     */
    public function getExtractionSourceName(): string
    {
        return self::EXTRACTION_SOURCE_DOM;
    }

    /**
     * @throws Exception
     */
    public function hasAnyConfigurationActive(?string $country): bool
    {
        $configs = $this->configurationReader->getConfigurationNamesForTypeAndCountry($country, $this->getExtractionSourceName());
        return !empty($configs);
    }

    /**
     * @param JobSearchConfigurationDomHtml $jobSearchConfigurationDomHtml
     * @param LoggerInterface               $logger
     * @param KernelInterface               $kernel
     */
    public function __construct(
        JobSearchConfigurationDomHtml    $jobSearchConfigurationDomHtml,
        LoggerInterface                  $logger,
        private readonly KernelInterface $kernel
    )
    {
        $this->logger                        = $logger;
        $this->jobSearchConfigurationDomHtml = $jobSearchConfigurationDomHtml;
        $this->configurationReader           = $kernel->getContainer()->get(ConfigurationReader::class);
        $this->dynamicDelayDecider           = $kernel->getContainer()->get(DynamicDelayDecider::class);
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function getOffersForAllConfigurations(array $keywords, int $maxPaginationPagesToScrap, JobOfferExtraction $jobOfferExtraction): array
    {
        $jobOfferSearchResults = [];
        foreach ($this->jobSearchConfigurationDomHtml->getJobSearchConfigurations($jobOfferExtraction->getCountry()) as $configurationName => $configuration) {
            $this->logger->info("Extracting job offers", [
                "configurationName" => $configurationName,
                "source"            => $this->getExtractionSourceName(),
            ]);
            try {
                $offersDto = $this->getSingleConfigSearchResults(
                    $configuration,
                    $keywords,
                    $maxPaginationPagesToScrap,
                    $jobOfferExtraction->getLocation(),
                    $jobOfferExtraction->getDistance(),
                    $jobOfferExtraction->getCountry()
                );

                $limit         = $jobOfferExtraction->getOffersLimit();
                $limitedOffers = OfferExtractionLimiterService::getLimitedOffers($offersDto, $limit);

                $jobOfferSearchResults[$configurationName] = $limitedOffers;
            } catch (ExternalProxyNotReachableException $epn) {
                throw $epn;
            } catch (Exception|TypeError $e) {
                $this->logger->critical("Failed extracting offers for configuration: {$configurationName}, trying with next one", [
                    "exception" => [
                        "message" => $e->getMessage(),
                        "trace"   => $e->getTraceAsString(),
                        "class"   => $e::class,
                    ]
                ]);
                continue;
            }
        }

        return $jobOfferSearchResults;
    }

    /**
     * Will take the {@see MainConfigurationDto} and will use it to extract the data from page.
     * Returns array of {@see SearchResultDto} for give configuration.
     *
     * @param MainConfigurationDto $mainConfigurationDto
     * @param array                $keywords
     * @param int                  $maxPaginationPagesToScrap
     * @param string|null          $locationName
     * @param int|null             $distance
     * @param string|null          $country
     *
     * @return NewAndExistingOffersDto
     * @throws ExternalProxyNotReachableException
     * @throws JobServiceCallableResolverException
     */
    public function getSingleConfigSearchResults(
        MainConfigurationDto $mainConfigurationDto,
        array $keywords,
        int $maxPaginationPagesToScrap,
        ?string $locationName,
        ?int    $distance,
        ?string $country = null,
    ): NewAndExistingOffersDto
    {
        $searchParams            = new JobSearchParameterBag($keywords, $maxPaginationPagesToScrap, $distance, $locationName, $country);
        $newAndExistingOffersDto = new NewAndExistingOffersDto();
        $crawlDelay              = null; // first call does not require delay
        $allExistingEntities     = [];
        $searchResultsDto        = [];
        $urlHandlerService       = new UrlHandlerServiceDomHtml($mainConfigurationDto, $this->kernel);
        $allPaginationUrls       = $urlHandlerService->buildPaginationUrls($searchParams);

        foreach($allPaginationUrls as $index => $paginationUrl){
            $pageNumber = $index + 1;
            try{
                $extractorService = new ExtractorServiceDomHtml(
                    $mainConfigurationDto,
                    $this->kernel,
                );

                $newAndExistingOffersDtoForPagination = $extractorService->getSearchResultsForPaginationUrl($searchParams, $paginationUrl, $pageNumber, $crawlDelay);
                $searchResultsDto                     = array_merge($searchResultsDto, $newAndExistingOffersDtoForPagination->getAllSearchResultDtos());
                $allExistingEntities                  = array_merge($allExistingEntities, $newAndExistingOffersDtoForPagination->getExistingOfferEntities());
            } catch (ExternalProxyNotReachableException $epn) {
                throw $epn;
            } catch (Exception|TypeError $e) {
                $this->logger->critical("Something went wrong while calling: " . __FUNCTION__ . ". Skipping given pagination Uri and trying with next one.", [
                    'crawledHost'   => $mainConfigurationDto->getHost(),
                    'paginationUrl' => $paginationUrl,
                    "exception"     => [
                        "message"   => $e->getMessage(),
                        "trace"     => $e->getTraceAsString(),
                    ],
                ]);

            }

            $crawlDelay  = $mainConfigurationDto->getCrawlerConfigurationDto()->getCrawlDelay();
            $crawlDelay += $this->dynamicDelayDecider->decide();
        }

        $newAndExistingOffersDto->setExistingOfferEntities($allExistingEntities);
        $newAndExistingOffersDto->setAllSearchResultDtos($searchResultsDto);

        return $newAndExistingOffersDto;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllConfigurationNames(string $country): array
    {
        $configurationNames = [];
        foreach ($this->jobSearchConfigurationDomHtml->getJobSearchConfigurations($country) as $configurationName => $configuration) {
            $configurationNames[] = $configurationName;
        }

        return $configurationNames;
    }

}