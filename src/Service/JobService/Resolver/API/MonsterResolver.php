<?php

namespace JobSearcher\Service\JobService\Resolver\API;

use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceCallableResolver;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\LocationDistanceAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MainConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchPageRequestBodyDataAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MaxPaginationPagesAwareTrait;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use JobSearcher\Service\Url\UrlService;
use WebScrapperBundle\Constants\UserAgentConstants;

/**
 * Methods in here are called via:
 * - {@see JobServiceCallableResolver}
 *
 * Methods themselves are set as callable inside
 * - "monster.de.yaml" (job service config file)
 *
 * Values resolver for the services:
 * - {@link https://monster.de/}
 *
 * Known issues:
 * - user agent gets abandoned from time to time, had {@see UserAgentConstants::CHROME_85} long time
 *   then it suddenly stopped working. Tested out {@see UserAgentConstants::POSTMAN_7_32_3} and it magically works...
 */
class MonsterResolver extends JobServiceResolver
{
    private const RESULTS_PER_PAGE = 10;

    use KeywordsAwareTrait;
    use MaxPaginationPagesAwareTrait;
    use SearchPageRequestBodyDataAwareTrait;
    use LocationNameAwareTrait;
    use LocationDistanceAwareTrait;
    use MainConfigurationDtoAwareTrait;

    /**
     * Must be equal to body sent in request, else server returns 403 etc.
     *
     * @param array $parameters
     *
     * @return int
     */
    public function calculateSearchUriHeaderContentLength(array $parameters): int
    {
        $detailPageRequestBodyData = $this->getSearchPageRequestBodyData($parameters);

        return strlen(json_encode($detailPageRequestBodyData));
    }

    /**
     * Keywords to search for
     *
     * @param array $parameters
     *
     * @return string
     */
    public function getSearchUriBodyQueryKeywordsString(array $parameters): string
    {
        $searchUriConfigDto = $this->getMainConfigurationDto($parameters)->getSearchUriConfigurationDto();
        $keywords           = $this->getKeywords($parameters);
        $gluedKw            = KeywordHandlerService::glueAll(
            $keywords,
            $searchUriConfigDto->isEncodeQuery(),
            $searchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $searchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        return $gluedKw;
    }

    /**
     * @return int
     */
    public function getSearchUriOffset(): int
    {
        return 0;
    }

    /**
     * Defines how max old the offers are allowed to be
     * In case of wanting to get fresher (2 weeks) etc., it has to be extracted from page by changing the filter
     * then analyzing the request
     *
     * @return string
     */
    public function getSearchUriBodyActivationRecency(): string
    {
        return "last month";
    }

    /**
     * This value must be different with each request, else the service caches some configuration and
     * returns wrong offers. Case was:
     * - starting search for Germany,
     * - then doing search for France (for same id),
     * - this would yield german offers,
     *
     * @return string
     */
    public function getSearchId(): string
    {
        $originalRequestString = 'd42aeb22-9a39-4510-891b-5cd3c8f62f1b';
        return substr(
              md5($originalRequestString)
            . md5($originalRequestString)
            . md5($originalRequestString),
              0,
              strlen($originalRequestString)
        )
       . md5(uniqid());
    }

    /**
     * This value must be different with each request, else the service caches some configuration and
     * returns wrong offers. Case was:
     * - starting search for Germany,
     * - then doing search for France (for same id),
     * - this would yield german offers,
     *
     * @return string
     */
    public function getFingerPrintId(): string
    {
        $originalRequestString = 'zd03cf94d7751b8490a06e595116c5821';

        return substr(
             md5($originalRequestString)
           . md5($originalRequestString)
           . md5($originalRequestString),
             0,
             strlen($originalRequestString
           )
       ) . md5(uniqid());
    }

    /**
     * This defines how many results are going to be returned on call
     *
     * @param array $parameters
     *
     * @return int
     */
    public function calculateSearchUriPageSize(array $parameters): int
    {
        $maxPagination = $this->getMaxPaginationPages($parameters);
        $maxPagination = ($maxPagination === 0 ? 1 : $maxPagination);

        return self::RESULTS_PER_PAGE * $maxPagination;
    }

    /**
     * Will return the addresses / locations to which the search results should be limited for
     *
     * @param array $parameters
     *
     * @return array
     */
    public function getLocations(array $parameters): array
    {
        $distance         = $this->getLocationDistance($parameters);
        $mainConfigDto    = $this->getMainConfigurationDto($parameters);
        $allowedDistances = $mainConfigDto->getSearchUriConfigurationDto()
                                          ->getLocationDistanceConfiguration()
                                          ->getAllowedDistances();

        $distanceKey = null;
        if (!is_null($distance)) {
            $distanceKey = ArrayTypeProcessor::getKeyForClosestNumber($distance, $allowedDistances);
        }

        $locationArray = [
            [
                "address" => $this->getLocationName($parameters),
                "country" => Iso3166CountryCodeEnum::get2digitFor3digit($mainConfigDto->getSupportedCountry())
            ]
        ];

        if (!empty($distanceKey)) {
            $locationArray[0]['radius'] = [
                "unit"  => "km",
                "value" => $distanceKey,
            ];
        }

        return $locationArray;
    }

    /**
     * @return string
     */
    public function getSearchUriHeaderContentType(): string
    {
        return "application/json; charset=UTF-8";
    }

    /**
     * @return string
     */
    public function getSearchUriHeaderHost(): string
    {
        return "appsapi.monster.io";
    }

    /**
     * @return string
     */
    public function getSearchUriHeaderUserAgent(): string
    {
        return UserAgentConstants::POSTMAN_7_32_3;
    }

    /**
     * @return int[]
     */
    public function getSearchUriBodyJobsAdsPosition(): array
    {
        return [1];
    }

    /**
     * @return string
     */
    public function getSearchUriBodyPlacementChildren(): string
    {
        return "WEB";
    }

    /**
     * @return string
     */
    public function getSearchUriBodyPlacementLocation(): string
    {
        return "JobSearchPage";
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getSearchUriBodyPlacementProperty(array $parameters): string
    {
        $mainConfigDto = $this->getMainConfigurationDto($parameters);
        return UrlService::getDomain($mainConfigDto->getHost());
    }

    /**
     * @return string
     */
    public function getSearchUriBodyPlacementType(): string
    {
        return "JOB_SEARCH";
    }

    /**
     * @return string
     */
    public function getSearchUriBodyPlacementView(): string
    {
        return "SPLIT";
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        // nothing here
    }
}