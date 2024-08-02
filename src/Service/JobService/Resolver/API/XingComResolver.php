<?php

namespace JobSearcher\Service\JobService\Resolver\API;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\DetailPageAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationDistanceAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MainConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MaxPaginationPagesAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchPageRequestBodyDataAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchResultDataAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use Symfony\Component\DomCrawler\Crawler;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;

/**
 * Methods in here are called via:
 * - {@see JobServiceCallableResolver}
 *
 * Methods themselves are set as callable inside "xing.com.yaml" (job service config file)
 *
 * Values resolver for the service:
 * - {@link https://www.xing.com/}
 */
class XingComResolver extends JobServiceResolver
{
    use MainConfigurationDtoAwareTrait;
    use KeywordsAwareTrait;
    use SearchResultDataAwareTrait;
    use DetailPageAwareTrait;
    use LocationNameAwareTrait;
    use LocationDistanceAwareTrait;
    use MaxPaginationPagesAwareTrait;
    use SearchPageRequestBodyDataAwareTrait;
    use SearchUriAwareTrait;

    private const RESULTS_PER_PAGE = 20;

    /**
     * Unknown what it is - was extracted from the API calls, is just needed
     *
     * @return string
     */
    public function getSearchPageOperationNameBodyParameter(): string
    {
        return "jobSearchByQuery";
    }

    /**
     * This query defines what data is getting returned from the API
     * must be in one line (more examples of usage are in `xing.com.yaml`
     *
     * @return string
     */
    public function getSearchPageQueryBodyParameter(): string
    {
        return 'query jobSearchByQuery($query: JobSearchQueryInput!, $consumer: String!, $offset: Int, $limit: Int, $sort: String, $trackRecent: Boolean) {  jobSearchByQuery(    query: $query    consumer: $consumer    offset: $offset    limit: $limit    sort: $sort    trackRecent: $trackRecent    returnAggregations: true    splitBenefit: true  ) {    total    searchQuery {      guid      body {        ...JobSearchQueryBodySplitBenefits        __typename      }      __typename    }    collection {      ...JobItemResult      __typename    }    aggregations {      employmentTypes {        ...EmploymentTypeAggregation        __typename      }      careerLevels {        ...CareerLevelAggregation        __typename      }      disciplines {        ...DisciplineAggregation        __typename      }      industries {        ...IndustryAggregation        __typename      }      benefitsEmployeePerk {        ...BenefitAggregation        __typename      }      benefitsWorkingCulture {        ...BenefitAggregation        __typename      }      countries {        ...CountryAggregation        __typename      }      cities {        ...CityAggregation        __typename      }      remoteOptions {        ...RemoteOptionAggregation        __typename      }      __typename    }    __typename  }}fragment JobSearchQueryBodySplitBenefits on JobSearchQueryBody {  keywords  location {    text    radius    city {      id      name      __typename    }    __typename  }  filterCollection {    ...JobSearchFilterCollectionSplitBenefits    __typename  }  __typename}fragment JobSearchFilterCollectionSplitBenefits on JobFilterCollection {  ...CompanyFilter  ...EmploymentTypeFilter  ...CareerLevelFilter  ...DisciplineFilter  ...IndustryFilter  ...BenefitEmployeePerkFilter  ...BenefitWorkingCultureFilter  ...CountryFilter  ...CityFilter  ...SalaryFilter  ...RemoteOptionFilter  __typename}fragment CompanyFilter on JobFilterCompany {  company {    companyName    __typename  }  entityId  __typename}fragment EmploymentTypeFilter on JobFilterEmploymentType {  employmentType {    localizationValue    __typename  }  entityId  __typename}fragment CareerLevelFilter on JobFilterCareerLevel {  careerLevel {    localizationValue    __typename  }  entityId  __typename}fragment DisciplineFilter on JobFilterDiscipline {  discipline {    localizationValue    __typename  }  entityId  __typename}fragment IndustryFilter on JobFilterIndustry {  industry {    localizationValue    __typename  }  entityId  __typename}fragment BenefitEmployeePerkFilter on JobFilterBenefitEmployeePerk {  benefitEmployeePerk {    localizationValue    __typename  }  entityId  __typename}fragment BenefitWorkingCultureFilter on JobFilterBenefitWorkingCulture {  benefitWorkingCulture {    localizationValue    __typename  }  entityId  __typename}fragment CountryFilter on JobFilterCountry {  country {    localizationValue    __typename  }  entityId  __typename}fragment CityFilter on JobFilterCity {  city {    localizationValue: name    __typename  }  entityId  __typename}fragment SalaryFilter on JobFilterSalary {  min  max  __typename}fragment RemoteOptionFilter on JobFilterRemoteOption {  remoteOption {    localizationValue    __typename  }  entityId  __typename}fragment JobItemResult on JobItemResult {  trackingToken  position  descriptionHighlight  jobDetail {    ...JobTeaserVisibleJob    __typename  }  matchingHighlightsV2 {    ...JobMatchingHighlightsV2    __typename  }  __typename}fragment JobTeaserVisibleJob on VisibleJob {  id  slug  url  title  date: activatedAt  location {    city    __typename  }  employmentType {    localizationValue    __typename  }  companyInfo {    companyNameOverride    company {      id      logos {        x1: logo128px        x2: logo256px        __typename      }      kununuData {        ratingCount        ratingAverage        __typename      }      __typename    }    __typename  }  salary {    ... on Salary {      currency      amount      __typename    }    ... on SalaryRange {      currency      minimum      maximum      __typename    }    ... on SalaryEstimate {      currency      minimum      maximum      __typename    }    __typename  }  userInteractions {    bookmark {      state      __typename    }    __typename  }  __typename}fragment JobMatchingHighlightsV2 on JobMatchingHighlightsV2 {  token  highlight {    type    localization {      localizationValue      __typename    }    localizationA11y {      localizationValue      __typename    }    __typename  }  matchingFacts {    ...JobKeyfactV2    __typename  }  nonMatchingFacts {    ...JobKeyfactV2    __typename  }  __typename}fragment JobKeyfactV2 on JobMatchingHighlightsJobKeyfactV2 {  __typename  type  localization {    localizationValue    __typename  }  localizationA11y {    localizationValue    __typename  }  ... on JobMatchingHighlightsJobKeyfactSalaryV2 {    value {      ... on Salary {        currency        amount        __typename      }      ... on SalaryRange {        minimum        maximum        currency        __typename      }      ... on SalaryEstimate {        minimum        __typename      }      __typename    }    __typename  }}fragment EmploymentTypeAggregation on JobAggregationEmploymentType {  count  id  employmentType {    localizationValue    __typename  }  __typename}fragment CareerLevelAggregation on JobAggregationCareerLevels {  count  id  careerLevel {    localizationValue    __typename  }  __typename}fragment DisciplineAggregation on JobAggregationDiscipline {  count  id  discipline {    localizationValue    __typename  }  __typename}fragment IndustryAggregation on JobAggregationIndustry {  count  id  industry {    localizationValue    __typename  }  __typename}fragment BenefitAggregation on JobAggregationBenefit {  count  id  benefit {    localizationValue    __typename  }  __typename}fragment CountryAggregation on JobAggregationCountry {  count  id  country {    localizationValue    __typename  }  __typename}fragment CityAggregation on JobAggregationCity {  count  id  city {    localizationValue: name    __typename  }  __typename}fragment RemoteOptionAggregation on JobAggregationRemoteOption {  id  remoteOption {    localizationValue    __typename  }  __typename}';
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getSearchPageLocation(array $parameters): string
    {
        return $this->getLocationName($parameters) ?? '';
    }

    /**
     * @param array $parameters
     *
     * @return int
     */
    public function getSearchPageDistance(array $parameters): int
    {
        $distance         = $this->getLocationDistance($parameters);
        $allowedDistances = $this->getMainConfigurationDto($parameters)
                                 ->getSearchUriConfigurationDto()
                                 ->getLocationDistanceConfiguration()
                                 ->getAllowedDistances();

        $usedDistance = null;
        if (!is_null($distance)) {
            $usedDistance = ArrayTypeProcessor::getKeyForClosestNumber($distance, $allowedDistances);
        }

        return $usedDistance ?? 0;
    }

    /**
     * @return string
     */
    public function getDetailPageContentTypeHeader(): string
    {
        return "application/json";
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getSearchPageKeywordsString(array $parameters): string
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
     * @return bool
     */
    public function getSearchPageTrackRecentBodyParam(): bool
    {
        return true;
    }

    /**
     * @param array $parameters
     *
     * @return int
     */
    public function getSearchPageOffersLimit(array $parameters): int
    {
        $maxPagination = $this->getMaxPaginationPages($parameters);
        $maxPagination = ($maxPagination === 0 ? 1 : $maxPagination);

        return self::RESULTS_PER_PAGE * $maxPagination;
    }

    /**
     * Handles building search uri (it's static, since it's API call, and pagination offset etc. goes into request body)
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        return $this->getSearchUri($parameters);
    }

    /**
     * @return int
     */
    public function getSearchPageOffersOffset(): int
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getSearchPageConsumerBodyParam(): string
    {
        return "loggedout.web.jobs.search_results.center";
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function buildDetailPageDataArray(array $parameters): array
    {
        $calledUri      = $this->getDetailPageUrl($parameters);
        $crawlerService = $this->kernel->getContainer()->get(CrawlerService::class);
        $crawlerConfig  = new CrawlerConfigurationDto($calledUri, CrawlerService::CRAWLER_ENGINE_GOUTTE);
        $crawler        = $crawlerService->crawl($crawlerConfig);

        $descriptionSelectors = [
            'div[class^="html-description"] div[class^="html-description"]', // correct, there are 2 the same nested
        ];

        $descriptionContent = "";
        foreach ($descriptionSelectors as $selector) {
            foreach ($crawler->filter($selector) as $node) {
                $descriptionContent = (new Crawler($node))->html();
                if (!empty($descriptionContent)) {
                    break 2;
                }
            }
        }

        $dataArray = [
            "detail" => [
                'description' => $descriptionContent,
            ]
        ];

        return $dataArray;
    }

    /**
     * @return string
     */
    public function getSearchPageHeaderHost(): string
    {
        return 'www.xing.com';
    }

    /**
     * @return string
     */
    public function getSearchPageHeaderAccept(): string
    {
        return "*/*";
    }

    /**
     * @return string
     */
    public function getSearchPageHeaderContentType(): string
    {
        return "application/json";
    }

    /**
     * @param array $parameters
     *
     * @return int
     */
    public function calculateSearchPageHeaderContentLength(array $parameters): int
    {
        $detailPageRequestBodyData = $this->getSearchPageRequestBodyData($parameters);

        return strlen(json_encode($detailPageRequestBodyData));
    }

    public function init(): void
    {
        // nothing here
    }
}