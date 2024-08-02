<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Pl;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationDistanceAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use LogicException;

/**
 * Handles resolving data for service {@link https://www.aplikuj.pl/}, yaml file: `aplikuj.pl`
 */
class AplikujPlResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;
    use LocationDistanceAwareTrait;

    private const QUERY_KEYWORDS_PARAM = "keyword";

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        $baseSearchUriDto = $this->getBaseSearchUriConfigurationDto($parameters);
        $locationName     = $this->getLocationName($parameters);
        $usedDistance     = $this->getLocationDistance($parameters) ?? 0;
        $searchUri        = $this->getSearchUri($parameters);
        $queryParams      = [];
        $gluedKeywords    = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriDto->isEncodeQuery(),
            $baseSearchUriDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        if (!empty($usedDistance) && empty($locationName)) {
            throw new LogicException("Cannot build search uri, location distance is set but no location name was given");
        }

        if (!empty($locationName)) {
            $searchUri .= $locationName . DIRECTORY_SEPARATOR;
            $queryParams[$baseSearchUriDto->getLocationDistanceConfiguration()->getQueryParameter()] = $usedDistance;
        }

        $queryParams[self::QUERY_KEYWORDS_PARAM] = $gluedKeywords;

        $searchUri .= "strona-" . $this->getPageNumber($parameters);
        $searchUri .= "?" . http_build_query($queryParams);

        return $searchUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }
}