<?php

namespace JobSearcher\Service\JobSearch\UrlHandler;


use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\Exception\JobServiceCallableResolverException;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceCallableResolver;
use JobSearcher\Service\JobService\Resolver\ParametersEnum;
use JobSearcher\Service\Url\UrlService;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseDetailPageConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseMainConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BasePaginationOfferDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;
use JobSearcher\Service\JobSearch\UrlHandler\General\Location\LocationDistanceUrlHandlerService;
use JobSearcher\Service\JobSearch\UrlHandler\General\Location\LocationNameUrlHandlerService;
use JobSearcher\Service\JobService\ConfigurationBuilder\ConfigurationBuilderInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles any kind of logic related to building full crawl-able links, pagination urls etc.
 */
abstract class AbstractUrlHandlerService implements AbstractUrlHandlerInterface
{

    /**
     * @var BaseMainConfigurationDto $baseMainConfigurationDto
     */
    protected BaseMainConfigurationDto $baseMainConfigurationDto;

    /**
     * @var BaseDetailPageConfigurationDto $baseDetailPageConfigurationDto
     */
    protected BaseDetailPageConfigurationDto $baseDetailPageConfigurationDto;

    /**
     * @var BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto
     */
    protected BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto;

    /**
     * @var LocationDistanceUrlHandlerService $locationDistanceUrlHandlerService
     */
    private LocationDistanceUrlHandlerService $locationDistanceUrlHandlerService;

    /**
     * @var LocationNameUrlHandlerService $locationNameUrlHandlerService
     */
    private LocationNameUrlHandlerService $locationNameUrlHandlerService;


    /**
     * @param BaseMainConfigurationDto       $baseMainConfigurationDto
     * @param BaseDetailPageConfigurationDto $baseDetailPageConfigurationDto
     * @param BaseSearchUriConfigurationDto  $baseSearchUriConfigurationDto
     * @param KernelInterface                $kernel
     */
    public function __construct(
        BaseMainConfigurationDto       $baseMainConfigurationDto,
        BaseDetailPageConfigurationDto $baseDetailPageConfigurationDto,
        BaseSearchUriConfigurationDto  $baseSearchUriConfigurationDto,
        KernelInterface                $kernel
    )
    {
        $this->baseSearchUriConfigurationDto  = $baseSearchUriConfigurationDto;
        $this->baseDetailPageConfigurationDto = $baseDetailPageConfigurationDto;
        $this->baseMainConfigurationDto       = $baseMainConfigurationDto;

        $this->locationDistanceUrlHandlerService = $kernel->getContainer()->get(LocationDistanceUrlHandlerService::class);
        $this->locationNameUrlHandlerService     = $kernel->getContainer()->get(LocationNameUrlHandlerService::class);
    }

    /**
     * Will build absolute url
     *
     * @param string      $uri
     * @param string      $urlType
     * @param string|null $uriGluedString
     *
     * @return string
     */
    public function buildAbsoluteUrl(string $uri, string $urlType = self::URL_TYPE_PAGINATION_PAGE, ?string $uriGluedString = ""): string
    {
        // is already absolute url
        if (UrlService::isAbsoluteUri($uri)) {
            return $uri;
        }

        $host = null;
        if($urlType === self::URL_TYPE_DETAIL_PAGE){
            $host = $this->baseDetailPageConfigurationDto->getBaseHost();
        }elseif($urlType === self::URL_TYPE_PAGINATION_PAGE){
            $host = $this->baseSearchUriConfigurationDto->getSearchUriBaseHost();
        }

        if(empty($host)) {
            $host = $this->baseMainConfigurationDto->getHost();
        }

        $usedGluedString = (string)$uriGluedString;
        $absoluteUri     = $host . DIRECTORY_SEPARATOR;
        if (!empty($usedGluedString)) {
            $absoluteUri .= $usedGluedString . DIRECTORY_SEPARATOR;
        }

        $absoluteUri .= $uri;
        if (UrlService::hasUriLeadingSlash($uri)) {
            $absoluteUri = $host . $uri;
            if(!empty($usedGluedString)){
                $absoluteUri = $host . DIRECTORY_SEPARATOR . $usedGluedString . $uri;
            }
        }

        return $absoluteUri;
    }

    /**
     * Will return array of absolute urls for array of relative uri
     * Info:
     *  - do not strip urls from query strings here as in some cases like for example INDEED the url must have query params to work
     *
     * @param BasePaginationOfferDto[] $paginationOffersDto
     * @return BasePaginationOfferDto[]
     */
    public function buildPaginationOfferDtos(array $paginationOffersDto): array
    {
        foreach($paginationOffersDto as $paginationOfferDto){
            $absoluteUri = $this->buildAbsoluteUrl($paginationOfferDto->getJobOfferUrl());
            $paginationOfferDto->setAbsoluteJobOfferUrl($absoluteUri);
        }

        return $paginationOffersDto;
    }

    /**
     * Will return crawl-able pagination urls for job offers
     *
     * @param JobSearchParameterBag $searchParams
     *
     * @return array
     *
     * @throws JobServiceCallableResolverException
     */
    public function buildPaginationUrls(JobSearchParameterBag $searchParams): array
    {
        // This is necessary as normally loop should start from 0 and then "<=" is valid but "x" is used as multiplayer later so must be >= 1
        $realMaxPages    = $searchParams->getPaginationPagesCount() + 1;
        $startPageNumber = 1;
        $paginationUrls  = [];

        /**
         * Going over all uri types because for some keywords some services yield better result by standard search other by sorted
         * This also means that the more base uris there are the long search will take, so for pagination = 2, 4 calls are actually made
         */
        foreach ($this->baseSearchUriConfigurationDto->getBaseSearchUri()->getAllUris() as $baseSearchUri) {
            for ($x = $startPageNumber; $x <= $realMaxPages; $x++) {

                if ($x === 1) {
                    $currPaginationValue = $this->baseSearchUriConfigurationDto->getPaginationFirstPageValue() ?? $this->baseSearchUriConfigurationDto->getPaginationStartValue();
                } else {

                    /**
                     * That's some special handling because some pages have special pagination number which is out of
                     * order when compared to other pages. So when the service uses the first page value the multiplier
                     * has to be set to (-2) because the pagination counter should continue increasing normally as
                     * if it would've been first page so:
                     * - firstPage = 0,
                     * - 2nd page  = 2
                     * - 3rd page  = 3 (incrementor = 1)
                     */
                    $paginationMultiplier = (!is_null($this->baseSearchUriConfigurationDto->getPaginationFirstPageValue()) ? ($x - 2) : ($x - 1));
                    $currPaginationValue  = $this->baseSearchUriConfigurationDto->getPaginationStartValue() + ($this->baseSearchUriConfigurationDto->getPaginationIncrementValue() * $paginationMultiplier);
                }

                $paginationUrls[] = $this->buildOnePaginationUrl($searchParams->getKeywords(), $searchParams->getLocation(), $searchParams->getDistance(), $baseSearchUri, $currPaginationValue);
            }
        }

        return $paginationUrls;
    }

    /**
     * {@see AbstractUrlHandlerService::buildPaginationUrls()}
     *
     * @param array       $keywords
     * @param string|null $locationName
     * @param int|null    $distance
     * @param string      $baseSearchUri
     * @param string|null $paginationValue
     *
     * @return string
     * @throws JobServiceCallableResolverException
     */
    private function buildOnePaginationUrl(
        array      $keywords,
        ?string    $locationName,
        ?int       $distance,
        string     $baseSearchUri,
        ?string $paginationValue
    ): string
    {
        if ($this->baseSearchUriConfigurationDto->isUsingResolver()) {
            $usedUri     = $this->resolvePaginationUrl($keywords, $locationName, $distance, $baseSearchUri, $paginationValue);
            $absoluteUri = $this->buildAbsoluteUrl($usedUri);
            return $absoluteUri;
        }

        $usedUri = $baseSearchUri;
        if ($this->baseSearchUriConfigurationDto->isKeywordsPlacedInQuery()) {
            $usedUri = $this->buildPaginationUrlWithKeywords($keywords, $usedUri);
        }

        $usedUri         = $this->handleSearchByLocation($usedUri, $locationName, $distance);
        $absoluteUrl     = $this->buildAbsoluteUrl($usedUri);

        if (is_null($paginationValue)) {
            return $absoluteUrl;
        }

        $finalUrl = $absoluteUrl
            . "&"
            . $this->baseSearchUriConfigurationDto->getPaginationNumberQueryParameter()
            . "="
            . $paginationValue;

        return $finalUrl;
    }

    /**
     * Will append location-based-search url parts
     *
     * @param string      $uri
     * @param string|null $locationName
     * @param int|null    $distance
     *
     * @return string
     */
    private function handleSearchByLocation(string $uri, ?string $locationName, ?int $distance): string
    {
        $uri = $this->locationNameUrlHandlerService->append($uri, $locationName, $this->baseSearchUriConfigurationDto);
        $uri = $this->locationDistanceUrlHandlerService->append($uri, $distance, $this->baseSearchUriConfigurationDto);

        return $uri;
    }

    /**
     * Will build the pagination url from 0 meaning that the pagination url is friendly enough
     * so that the url can be built simply with some foreach, gluing keywords together etc.
     *
     * @param array $keywords
     * @param string $uri
     *
     * @return string
     */
    private function buildPaginationUrlWithKeywords(array $keywords, string $uri): string
    {
        if (str_contains($uri, ConfigurationBuilderInterface::KEYWORDS_URI_PLACEHOLDER)) {
            $this->replaceKeywordsPlaceholderTag($uri, $keywords);
            return $uri;
        }

        $gluedKeywords = KeywordHandlerService::glueAll(
            $keywords,
            $this->baseSearchUriConfigurationDto->isEncodeQuery(),
            $this->baseSearchUriConfigurationDto->getMultipleKeyWordsSeparatorCharacter(),
            $this->baseSearchUriConfigurationDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $uri .= $gluedKeywords;

        return $uri;
    }

    /**
     * If uri contains the {@see ConfigurationBuilderInterface::KEYWORDS_URI_PLACEHOLDER} then it will be
     * replaced with the keywords glued all together
     *
     * @param string $uri
     * @param array  $keywords
     *
     * @return string
     */
    private function replaceKeywordsPlaceholderTag(string $uri, array $keywords): string
    {
        $gluedKeywords = KeywordHandlerService::glueAll(
            $keywords,
            $this->baseSearchUriConfigurationDto->isEncodeQuery(),
            $this->baseSearchUriConfigurationDto->getMultipleKeyWordsSeparatorCharacter(),
            $this->baseSearchUriConfigurationDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $uri = str_replace(ConfigurationBuilderInterface::KEYWORDS_URI_PLACEHOLDER, $gluedKeywords, $uri);

        return $uri;
    }

    /**
     * Will attempt to resolve (build) the search uri by using the {@see JobServiceCallableResolver}
     *
     * @param array       $keywords
     * @param string|null $locationName
     * @param int|null    $distance
     * @param string      $baseSearchUri
     * @param string|null $pageNumber - can be null as in some cases pagination url is static do to API based calls,
     *
     * @return string
     * @throws JobServiceCallableResolverException
     */
    private function resolvePaginationUrl(
        array   $keywords,
        ?string $locationName,
        ?int    $distance,
        string  $baseSearchUri,
        ?string  $pageNumber
    ): string
    {
        $parameters = [
            ParametersEnum::KEYWORDS->name            => $keywords,
            ParametersEnum::LOCATION_DISTANCE->name   => $distance,
            ParametersEnum::LOCATION_NAME->name       => $locationName,
            ParametersEnum::SEARCH_URI->name          => $baseSearchUri,
            ParametersEnum::BASE_SEARCH_URI_DTO->name => $this->baseSearchUriConfigurationDto,
            ParametersEnum::PAGE_NUMBER->name         => $pageNumber,
            ParametersEnum::MAIN_CONFIGURATION_DTO->name => $this->baseMainConfigurationDto,
        ];

        $resolver = new JobServiceCallableResolver();
        $resolver->setClassMethodString($this->baseSearchUriConfigurationDto->getResolver());
        $resolvedValue = $resolver->resolveValue($parameters);

        return $resolvedValue;
    }

}