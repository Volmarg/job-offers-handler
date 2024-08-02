<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationDistanceAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use LogicException;

/**
 * Handles resolving data for service {@link https://jooble.org/}
 * - yaml: pl.jooble.org
 *
 * This is global website with subdomains per country / region so theoretically this one resolver should cover all the cases
 * unless the website varies between some countries
 */
class JoobleOrg extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use LocationDistanceAwareTrait;
    use PageNumberAwareTrait;

    private const QUERY_PARAM_KEYWORDS = "ukw";

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
        $distance               = $this->getLocationDistance($parameters);
        $locationName           = $this->getLocationName($parameters);
        $gluedKeywords          = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriConfigDto->isEncodeQuery(),
            $baseSearchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $queryParams[self::QUERY_PARAM_KEYWORDS]                                   = $gluedKeywords;
        $queryParams[$baseSearchUriConfigDto->getPaginationNumberQueryParameter()] = $pageNumber;

        if (!empty($distance) && empty($locationName)) {
            throw new LogicException("Cannot build search uri, location distance is set but no location name was given");
        }

        $allowedDistances = $baseSearchUriConfigDto->getLocationDistanceConfiguration()->getAllowedDistances();
        $distanceKey      = null;
        if (!is_null($distance)) {
            $distanceKey = ArrayTypeProcessor::getKeyForClosestNumber($distance, $allowedDistances);
        }

        if (!empty($locationName)) {
            $queryParams[$baseSearchUriConfigDto->getLocationNameConfiguration()->getQueryParameter()] = $locationName;

            if (!empty($distanceKey)) {
                $queryParams[$baseSearchUriConfigDto->getLocationDistanceConfiguration()->getQueryParameter()] = $distanceKey;
            }
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