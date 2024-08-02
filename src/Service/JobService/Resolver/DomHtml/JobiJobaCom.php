<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;

/**
 * Handles resolving data for service {@link https://www.jobijoba.com}
 *
 * This is global website with subdomains per country / region so theoretically this one resolver should cover all the cases
 * unless the website varies between some countries
 */
class JobiJobaCom extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;

    private const QUERY_PARAM_KEYWORDS = "what";
    private const QUERY_PARAM_WHERE_TYPE = "where_type";

    // no idea what this is but must be present when searching with location
    private const WHERE_TYPE_DEPARTMENT = "department";

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        $searchUri              = $this->getSearchUri($parameters);
        $queryParams            = [];

        $pageNumber             = $this->getPageNumber($parameters);
        $baseSearchUriConfigDto = $this->getBaseSearchUriConfigurationDto($parameters);
        $locationName           = $this->getLocationName($parameters);
        $gluedKeywords          = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriConfigDto->isEncodeQuery(),
            $baseSearchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $queryParams[self::QUERY_PARAM_KEYWORDS] = $gluedKeywords;
        if (!empty($locationName)) {
            $queryParams[self::QUERY_PARAM_WHERE_TYPE] = self::WHERE_TYPE_DEPARTMENT;
            $queryParams[$baseSearchUriConfigDto->getLocationNameConfiguration()->getQueryParameter()] = $locationName;
        }

        $queryParams[$baseSearchUriConfigDto->getPaginationNumberQueryParameter()] = $pageNumber;

        $searchUri .= "?" . http_build_query($queryParams);

        return $searchUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }
}