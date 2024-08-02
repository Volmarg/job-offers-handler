<?php

namespace JobSearcher\Service\JobSearch\Command\Extractor\Api;

use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Exception\Bundle\ProxyProvider\ExternalProxyNotReachableException;
use JobSearcher\Exception\JobServiceCallableResolverException;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;
use JobSearcher\Service\JobSearch\OfferExtractionLimiterService;
use JobSearcher\Service\JobSearch\Result\JobSearchResultService;
use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Service\JobSearch\Extractor\Api\ExtractorService                    as ExtractorServiceApi;
use JobSearcher\Service\JobSearch\UrlHandler\Api\UrlHandlerService;
use JobSearcher\Service\JobService\ConfigurationBuilder\Api\ApiConfigurationBuilder as JobSearchConfigurationApi;
use Exception;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Handles extracting job search offers for given source types,
 * For example:
 * - {@see ExtractorServiceApi}
 */
class ExtractorService implements ExtractorInterface
{

    /**
     * @var JobSearchConfigurationApi $jobSearchConfigurationApi
     */
    private JobSearchConfigurationApi $jobSearchConfigurationApi;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var JobSearchResultService $jobSearchResultService
     */
    private JobSearchResultService $jobSearchResultService;

    /**
     * @var ConfigurationReader $configurationReader
     */
    private readonly ConfigurationReader $configurationReader;

    /**
     * {@inheritDoc}
     */
    public function getExtractionSourceName(): string
    {
        return self::EXTRACTION_SOURCE_API;
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
     * @param JobSearchConfigurationApi $jobSearchConfigurationApi
     * @param LoggerInterface           $logger
     * @param JobSearchResultService    $jobSearchResultService
     * @param KernelInterface           $kernel
     */
    public function __construct(
        JobSearchConfigurationApi $jobSearchConfigurationApi,
        LoggerInterface           $logger,
        JobSearchResultService    $jobSearchResultService,
        private KernelInterface   $kernel
    )
    {
        $this->configurationReader       = $this->kernel->getContainer()->get(ConfigurationReader::class);
        $this->jobSearchResultService    = $jobSearchResultService;
        $this->jobSearchConfigurationApi = $jobSearchConfigurationApi;
        $this->logger                    = $logger;
    }

    /**
     * {@inheritDoc}
     *
     * @param array              $keywords
     * @param int                $maxPaginationPagesToScrap
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @return Array<NewAndExistingOffersDto>
     * @throws ExternalProxyNotReachableException
     * @throws GuzzleException
     */
    public function getOffersForAllConfigurations(array $keywords, int $maxPaginationPagesToScrap, JobOfferExtraction $jobOfferExtraction): array
    {
        if (empty($jobOfferExtraction->getCountry())) {
            throw new LogicException("Extraction country name is not set!");
        }

        $searchResults = [];
        foreach ($this->jobSearchConfigurationApi->getJobSearchConfigurations($jobOfferExtraction->getCountry()) as $configurationName => $configuration) {
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
                    $jobOfferExtraction->getDistance()
                );

                $limit            = $jobOfferExtraction->getOffersLimit();
                $limitedOffersDto = OfferExtractionLimiterService::getLimitedOffers($offersDto, $limit);

                $searchResults[$configurationName] = $limitedOffersDto;
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

        return $searchResults;
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
     * @throws GuzzleException
     * @throws JobServiceCallableResolverException
     */
    public function getSingleConfigSearchResults(
        MainConfigurationDto $mainConfigurationDto,
        array                $keywords,
        int                  $maxPaginationPagesToScrap,
        ?string              $locationName,
        ?int                 $distance,
        ?string              $country = null,
    ): NewAndExistingOffersDto
    {
        $extractorService  = new ExtractorServiceApi(
            $mainConfigurationDto,
            $keywords,
            $maxPaginationPagesToScrap,
            $locationName,
            $distance,
            $this->kernel
        );

        $urlHandlerService       = new UrlHandlerService($mainConfigurationDto, $this->kernel);
        $searchParams            = new JobSearchParameterBag($keywords, $maxPaginationPagesToScrap, $distance, $locationName, $country);
        $allPaginationUrls       = $urlHandlerService->buildPaginationUrls($searchParams);
        $newAndExistingOffersDto = $extractorService->buildSearchResults($allPaginationUrls);

        return $newAndExistingOffersDto;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllConfigurationNames(string $country): array
    {
        $configurationNames = [];
        foreach ($this->jobSearchConfigurationApi->getJobSearchConfigurations($country) as $configurationName => $configuration) {
            $configurationNames[] = $configurationName;
        }

        return $configurationNames;
    }
}