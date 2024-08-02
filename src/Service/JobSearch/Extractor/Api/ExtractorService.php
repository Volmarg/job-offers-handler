<?php

namespace JobSearcher\Service\JobSearch\Extractor\Api;

use DataParser\Service\Parser\Date\DateParser;
use DeepCopy\DeepCopy;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Exception\Bundle\ProxyProvider\ExternalProxyNotReachableException;
use JobSearcher\Exception\JobServiceCallableResolverException;
use JobSearcher\Exception\MissingResponseDataException;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Service\Bundle\ProxyProvider\ProxyProviderService;
use JobSearcher\Service\DOM\DomContentReducerService;
use JobSearcher\Service\DOM\TagsCleanerService;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;
use JobSearcher\Service\JobSearch\Crawler\DynamicDelayDecider;
use JobSearcher\Service\JobSearch\Extractor\AbstractExtractor;
use JobSearcher\Service\JobSearch\Scrapper\BaseScrapperService;
use JobSearcher\Service\JobSearch\UrlHandler\AbstractUrlHandlerInterface;
use JobSearcher\Service\JobSearch\UrlHandler\Api\UrlHandlerService;
use JobSearcher\Service\JobSearch\UrlHandler\Api\UrlHandlerService as UrlHandlerServiceApi;
use JobSearcher\Service\JobService\Resolver\API\Factory\JobOfferDetailPageUrlResolverFactory;
use JobSearcher\Service\JobService\Resolver\JobServiceCallableResolver;
use JobSearcher\Service\JobService\Resolver\ParametersEnum;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use JobSearcher\Service\Url\UrlService;
use Psr\Log\LoggerInterface;
use SmtpEmailValidatorBundle\Service\SmtpValidator;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

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
     * @var UrlHandlerService $urlHandlerService
     */
    private UrlHandlerService $urlHandlerService;

    /**
     * @var ExtractorResolverService $extractorResolverService
     */
    private ExtractorResolverService  $extractorResolverService;

    /**
     * @var JobSearchResultRepository $jobSearchResultRepository
     */
    private JobSearchResultRepository $jobSearchResultRepository;

    /**
     * @var LocationExtractorService $locationExtractorService
     */
    private LocationExtractorService $locationExtractorService;

    private readonly SmtpValidator $smtpValidator;
    private readonly int $maxDaysOffsetBeforeCreatingNewerDuplicate;
    private readonly LoggerInterface $offerExtractionLogger;
    private readonly DynamicDelayDecider $dynamicDelayDecider;
    private readonly CallHandlerService $callHandlerService;
    private readonly ProxyProviderService $proxyProviderService;

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     * @param array                $keywords
     * @param int                  $maxPaginationPagesToScrap
     * @param string|null          $locationName
     * @param int|null             $locationDistance
     * @param KernelInterface      $kernel
     */
    public function __construct(
        MainConfigurationDto $mainConfigurationDto,
        private array        $keywords,
        private int          $maxPaginationPagesToScrap,
        ?string              $locationName,
        ?int                 $locationDistance,
        KernelInterface      $kernel
    ) {
        $parameterBag = $kernel->getContainer()->get('parameter_bag_public');

        $this->offerExtractionLogger     = $kernel->getContainer()->get('monolog.logger.offerExtraction');
        $this->jobSearchResultRepository = $kernel->getContainer()->get(JobSearchResultRepository::class);
        $this->locationExtractorService  = $kernel->getContainer()->get(LocationExtractorService::class);
        $this->extractorResolverService  = $kernel->getContainer()->get(ExtractorResolverService::class);
        $this->smtpValidator             = $kernel->getContainer()->get(SmtpValidator::class);
        $this->dynamicDelayDecider       = $kernel->getContainer()->get(DynamicDelayDecider::class);
        $this->proxyProviderService      = $kernel->getContainer()->get(ProxyProviderService::class);

        $this->callHandlerService = $kernel->getContainer()->get(CallHandlerService::class);
        $this->callHandlerService->setMainConfigurationDto($mainConfigurationDto);

        $this->maxDaysOffsetBeforeCreatingNewerDuplicate = $parameterBag->get("max_days_offset_before_creating_newer_duplicate");

        // implemented the way that array counts, yet for real pagination on websites it has to start from 1
        $realMaxPagination = $maxPaginationPagesToScrap + 1;

        $this->extractorResolverService->setMainConfigurationDto($mainConfigurationDto);
        $this->extractorResolverService->setKeywords($keywords);
        $this->extractorResolverService->setLocationName($locationName);
        $this->extractorResolverService->setLocationDistance($locationDistance);
        $this->extractorResolverService->setMaxPaginationPages($realMaxPagination);

        $this->urlHandlerService    = new UrlHandlerServiceApi($mainConfigurationDto, $kernel);
        $this->mainConfigurationDto = $mainConfigurationDto;
    }

    /**
     * Will return array of jobs details data
     * At this moment given are not supported:
     * - salary,
     * - contact data,
     *
     * @param array $allPaginationUrls
     *
     * @return NewAndExistingOffersDto
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function buildSearchResults(array $allPaginationUrls): NewAndExistingOffersDto
    {
        $newAndExistingOffersDto        = new NewAndExistingOffersDto();
        $jobsInformationFromPagination  = $this->getAllJobsInformationFromPagination($allPaginationUrls);
        $allJobOfferSearchResults       = [];
        $allExistingOfferIds            = [];
        $crawlDelayMs                   = $this->mainConfigurationDto->getCrawlDelay();
        $crawlDelayMs                  += $this->dynamicDelayDecider->decide();
        $crawlDelay                    = (int) $crawlDelayMs / 1000;

        foreach($jobsInformationFromPagination as $jobInformationFromPagination){
            foreach($jobInformationFromPagination as $jobInformation){

                try{
                    $this->proxyProviderService->checkProxyReachability();
                    $existingOfferIds = $this->findExistingOfferIdsForPaginationInfo($jobInformation);
                    if (!empty($existingOfferIds)) {
                        $allExistingOfferIds = array_unique(array_merge($existingOfferIds, $allExistingOfferIds));
                        continue;
                    }

                    $jobOfferDetails = [];
                    if ($this->mainConfigurationDto->getDetailPageConfigurationDto()->isScrappable()) {
                        $jobOfferDetails = $this->resolveJobOfferDetails($jobInformation, $crawlDelay);
                    }

                    // mixing both: results from details and pagination for easier extraction with the json path later on
                    $dataArrays                 = [$jobInformation, $jobOfferDetails];
                    $allJobOfferSearchResults[] = $this->buildOfferSearchResult($dataArrays);
                } catch (ExternalProxyNotReachableException $epn) {
                    throw $epn;
                } catch (Exception|TypeError $e) {
                    $this->offerExtractionLogger->critical("Could not build search result for provided data ('detail page' & 'pagination') in extractor", [
                        "info"              => "Skipping this entry and trying with next one",
                        "extractionSource"  => ExtractorInterface::EXTRACTION_SOURCE_API,
                        "configurationName" => $this->mainConfigurationDto->getConfigurationName(),
                        "shortNote"         => "Issue extracting data from api arrays",
                        "exceptions" => [
                            "message" => $e->getMessage(),
                            "trace"   => $e->getTraceAsString(),
                        ],
                        "data" => $dataArrays ?? null,
                    ]);
                }
            }

        }

        $existingOffers = $this->jobSearchResultRepository->findBy(["id" => $allExistingOfferIds]);
        $newAndExistingOffersDto->setAllSearchResultDtos($allJobOfferSearchResults);
        $newAndExistingOffersDto->setExistingOfferEntities($existingOffers);

        return $newAndExistingOffersDto;
    }

    /**
     * Will return all jobs details data
     *
     * @param array $paginationUris
     *
     * @return array
     * @throws GuzzleException
     * @throws ExternalProxyNotReachableException
     */
    private function getAllJobsInformationFromPagination(array $paginationUris): array
    {
        $jobsInformation = [];
        foreach($paginationUris as $paginationUrl){
            try {
                $this->proxyProviderService->checkProxyReachability();

                // headers and body parameters got to be cloned as resolver replaces the dto values
                $clonedBodyParameters = (new DeepCopy())->copy($this->mainConfigurationDto->getSearchUriConfigurationDto()->getRequestRawBody());
                $clonedHeaders        = (new DeepCopy())->copy($this->mainConfigurationDto->getSearchUriConfigurationDto()->getRequestHeaders());

                // resolver must be called like this because the "headers" in some services (monster.de) rely on the request body
                $this->extractorResolverService->ensureVariableSet();
                $this->extractorResolverService->resolveRequestBodyParameters($clonedBodyParameters);

                $requestBodyData = $this->callHandlerService->buildBodyParametersArray($clonedBodyParameters);
                $this->extractorResolverService->setSearchPageRequestBodyData($requestBodyData);

                $this->extractorResolverService->resolveHeaders($clonedHeaders);

                $arrayOfData = $this->callHandlerService->makeCall(
                    $this->mainConfigurationDto->getSearchUriConfigurationDto()->getMethod(),
                    $paginationUrl,
                    $clonedHeaders,
                    $clonedBodyParameters,
                    $requestBodyData,
                );
            } catch (ExternalProxyNotReachableException $epn) {
                throw $epn;
            } catch (Exception|TypeError $e) {
                $this->offerExtractionLogger->critical("Could not get data from pagination url", [
                    "extractionSource"  => ExtractorInterface::EXTRACTION_SOURCE_API,
                    "configurationName" => $this->mainConfigurationDto->getConfigurationName(),
                    "shortNote"         => "Issue getting data from pagination",
                    "paginationUrl" => $paginationUrl,
                    "exception" => [
                        "message" => $e->getMessage(),
                        "trace"   => $e->getTraceAsString(),
                        "class"   => $e::class,
                    ]
                ]);
                continue;
            }

            $jobsInformation[] = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($arrayOfData, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getAllJobsData());
            $crawlDelay        = $this->mainConfigurationDto->getCrawlDelay() / 1000;
            if (!is_null($crawlDelay)) {
                sleep((int)$crawlDelay);
            }
        }

        return $jobsInformation;
    }

    /**
     * Will call the detail page and will fetch job offer details, additionally if there is a defined
     * json path which says "there is some more information in given node" then it will return not only
     * the information from the detail page itself but also from an extra section which might have detail data stored there
     *
     * [Xing.com] is an use case for that as the json structure looks something like that:
     *  {
     *      "data": {
     *          "job": { }
     *      }
     * }
     *
     * - the job details are stored under `data.job`
     *
     * Other api can return all the information about job just like that (without the `extra section`)
     * {
     *      "title": "",
     *      "description": ""
     * }
     *
     * @param array $jobInformationFromPagination
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    private function getJobOfferDetailsFromJsonConfig(array $jobInformationFromPagination): array
    {
        $this->extractorResolverService->setSearchResultData($jobInformationFromPagination);

        // headers and body parameters got to be cloned as resolver replaces the dto values
        $clonedBodyParameters = (new DeepCopy())->copy($this->mainConfigurationDto->getDetailPageConfigurationDto()->getRequestRawBody());
        $clonedHeaders        = (new DeepCopy())->copy($this->mainConfigurationDto->getDetailPageConfigurationDto()->getRequestHeaders());

        $this->extractorResolverService->ensureVariableSet();
        $this->extractorResolverService->resolveRequestBodyParameters($clonedBodyParameters);
        $this->extractorResolverService->resolveHeaders($clonedHeaders);

        $detailPageUrl  = $this->urlHandlerService->buildAbsoluteUrlToDetailPage($jobInformationFromPagination);
        $detailPageData = $this->callHandlerService->makeCall(
            $this->mainConfigurationDto->getDetailPageConfigurationDto()->getMethod(),
            $detailPageUrl,
            $clonedHeaders,
            $clonedBodyParameters,
        );

        $returnedData = $detailPageData;
        if( !empty($this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobDetailMoreInformation()) ){
            $moreInformationData = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($detailPageData, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobDetailMoreInformation());
            if( is_null($moreInformationData) ){

                $this->offerExtractionLogger->warning("Could not extract the data for given structure", [
                    "extractionSource"  => ExtractorInterface::EXTRACTION_SOURCE_API,
                    "configurationName" => $this->mainConfigurationDto->getConfigurationName(),
                    "shortNote"         => "Issue extracting data from api arrays",
                    "arrayToSearchIn"   => $detailPageData,
                    "searchedKeys"      => $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobDetailMoreInformation(),
                ]);

            }else{
                $returnedData = array_merge_recursive($returnedData, $moreInformationData);
            }
        }

        return $returnedData;
    }

    /**
     * In some cases the pagination data array returns enough data to determine if offer already exists in DB or not,
     * if it does exist then call can be skipped & thus it saves some time.
     *
     * @param array $jobInformation
     *
     * @return int[]
     * @throws Exception
     */
    private function findExistingOfferIdsForPaginationInfo(array $jobInformation): array
    {
        $createdAfterDate   = (new \DateTime())->modify("-{$this->maxDaysOffsetBeforeCreatingNewerDuplicate} DAYS");
        $companyName        = DataExtractorService::extractDataFromOneOfDataArrays([$jobInformation], $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getCompanyName());
        $offerTitle         = DataExtractorService::extractDataFromOneOfDataArrays([$jobInformation], $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobTitle());
        $offerHash          = md5($companyName . $offerTitle);
        $entityIdsForHashes = $this->jobSearchResultRepository->getIdsForCompanyNameAndJobTitleHashes([$offerHash], $createdAfterDate);
        if (!empty($entityIdsForHashes)) {
            return array_keys($entityIdsForHashes);
        }

        $jobOfferUrl = DataExtractorService::extractDataFromOneOfDataArrays([$jobInformation], $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobOfferUrl());
        if (!is_null($jobOfferUrl)) {
            $queryLessUrl     = UrlService::stripQueryString($jobOfferUrl);
            $entityIdsForUrls = $this->jobSearchResultRepository->getExistingOfferIdsForUrls([$queryLessUrl], $createdAfterDate);
            if (!empty($entityIdsForUrls)) {
                return array_keys($entityIdsForUrls);
            }
        }

        return [];
    }

    /**
     * Will build {@see SearchResultDto} using fetched data arrays,
     * These are plain arrays because each api can have different data structure etc.
     * That's why the "json" configuration exists for each supported service (to read the data from arrays)
     *
     * @param array $dataArrays
     *
     * @return SearchResultDto
     * @throws Exception
     */
    private function buildOfferSearchResult(array $dataArrays): SearchResultDto
    {
        /**
         * Need to check if the offer url is fully valid url or is it just an uri or slug, due to:
         * Example:
         * - xing         - returns full url,
         * - nofluffyjobs - returns uri,
         *
         * If that's an uri then it's being used to build absolute url
         */
        if (!empty($this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobOfferUrl())) {
            $jobOfferUrl = DataExtractorService::extractDataFromOneOfDataArrays($dataArrays, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobOfferUrl());
            $jobOfferUrl = $this->urlHandlerService->buildAbsoluteUrl(
                $jobOfferUrl,
                AbstractUrlHandlerInterface::URL_TYPE_DETAIL_PAGE,
                $this->mainConfigurationDto->getDetailPageConfigurationDto()->getHostUriGlueString()
            );
        }else{
            $jobOfferUrl = JobOfferDetailPageUrlResolverFactory::resolveForConfigurationName($dataArrays, $this->mainConfigurationDto);
        }

        $jobDescription          = DataExtractorService::extractDataFromOneOfDataArrays($dataArrays, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobDescription());
        $jobDescription          = $this->cleanOfferDescription($jobDescription);

        $jobTitle                = DataExtractorService::extractDataFromOneOfDataArrays($dataArrays, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobTitle());
        $locations               = $this->locationExtractorService->extractLocationFromDataArrays($dataArrays, $this->mainConfigurationDto->getJsonStructureConfigurationDto());
        $companyName             = DataExtractorService::extractDataFromOneOfDataArrays($dataArrays, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getCompanyName());
        $jobPostedDateTimeString = DataExtractorService::extractDataFromOneOfDataArrays($dataArrays, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobPostedDateTime());

        $jobPostedDateTime    = DateParser::parseDateFromString($jobPostedDateTimeString);
        $isRemoteJobMentioned = BaseScrapperService::scrapMentionedThatRemoteIsPossible($jobDescription, $jobTitle, $locations, $this->mainConfigurationDto->getSupportedCountry());

        $contactEmail = "";
        if (empty($contactEmail)) {
            $contactEmail = BaseScrapperService::extractEmailFromString($jobDescription) ?? "";
        }

        if (!empty($contactEmail)) {
            $contactEmail = ($this->smtpValidator->doBaseValidation($contactEmail) ? $contactEmail : "");
        }

        $jobOfferSearchResult = new SearchResultDto();
        $jobOfferSearchResult->getJobDetailDto()->setJobTitle($jobTitle);
        $jobOfferSearchResult->getJobDetailDto()->setJobDescription(nl2br($jobDescription));
        $jobOfferSearchResult->getCompanyDetailDto()->setCompanyName($companyName);
        $jobOfferSearchResult->setJobPostedDateTime($jobPostedDateTime);
        $jobOfferSearchResult->setJobOfferUrl($jobOfferUrl);
        $jobOfferSearchResult->setJobOfferHost($this->mainConfigurationDto->getHost());
        $jobOfferSearchResult->setRemoteJobMentioned($isRemoteJobMentioned);
        $jobOfferSearchResult->getCompanyDetailDto()->setCompanyLocations($locations);
        $jobOfferSearchResult->getContactDetailDto()->setEmail($contactEmail);

        return $jobOfferSearchResult;
    }

    /**
     * Handle clearing the job description
     *
     * @param string $description
     *
     * @return string
     * @throws Exception
     */
    private function cleanOfferDescription(string $description): string
    {
        // surprisingly this is actually happening for some services
        if (empty($description)) {
            return "";
        }

        $crawler = new Crawler($description);
        $crawler = DomContentReducerService::handleDomNodeReducing(
            $crawler,
            $this->mainConfigurationDto->getDetailPageConfigurationDto()->getDescriptionRemovedElementsSelectors()
        );

        $description = TagsCleanerService::removeTags($crawler->html());

        return $description;
    }

    /**
     * Will attempt to resolve the detail page data by using the resolver.
     * The resolver was introduced due to the fact that some pages are partially relying on API calls.
     * For example search results will be fetched by API, but the detail page is generated with DOM content,
     *
     * With this solution, some additional information can be obtained from the detail page.
     *
     * @param array $informationArray
     * @return array
     *
     * @throws JobServiceCallableResolverException
     */
    private function resolveDetailPageOfferData(array $informationArray): array
    {
        $detailPageUrl  = $this->urlHandlerService->buildAbsoluteUrlToDetailPage($informationArray);

        $parameters = [
            ParametersEnum::DETAIL_PAGE_URL->name        => $detailPageUrl,
            ParametersEnum::MAIN_CONFIGURATION_DTO->name => $this->mainConfigurationDto,
        ];

        $resolver = new JobServiceCallableResolver();
        $resolver->setClassMethodString($this->mainConfigurationDto->getDetailPageConfigurationDto()->getOfferDataResolver());
        $detailPageOfferData = $resolver->resolveValue($parameters);

        return $detailPageOfferData;
    }

    /**
     * Will provide detail page data either from resolver, or from detail page results (extracted via json config from yaml)
     *
     * @param mixed     $jobInformation
     * @param float|int $crawlDelay
     *
     * @return array
     *
     * @throws GuzzleException
     * @throws JobServiceCallableResolverException
     * @throws MissingResponseDataException
     */
    public function resolveJobOfferDetails(mixed $jobInformation, float|int $crawlDelay): array
    {
        if ($this->mainConfigurationDto->getDetailPageConfigurationDto()->isUsingResolver()) {
            $jobOfferDetails = $this->resolveDetailPageOfferData($jobInformation);
        } else {
            $jobOfferDetails = $this->getJobOfferDetailsFromJsonConfig($jobInformation);
        }

        sleep((int)$crawlDelay);
        if (empty($jobOfferDetails)) {
            throw new MissingResponseDataException("Something went wrong while trying to fetch the job detail via " . self::class . ". Array is empty!");
        }

        return $jobOfferDetails;
    }

}