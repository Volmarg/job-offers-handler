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
 * Handles resolving data for service {@link https://www.goldenline.pl/}, yaml file: `goldenline.pl`
 */
class GoldenlinePlResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use LocationDistanceAwareTrait;
    use PageNumberAwareTrait;

    private const QUERY_DISTANCE_PARAM = "radius";
    private const QUERY_LOCATION_NAME_PARAM = "locations";

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        $pageNumber             = $this->getPageNumber($parameters);
        $locationName           = $this->getLocationName($parameters);
        $distance               = $this->getLocationDistance($parameters);
        $baseSearchUriConfigDto = $this->getBaseSearchUriConfigurationDto($parameters);

        if (!empty($distance) && empty($locationName)) {
            throw new LogicException("Cannot build search uri, location distance is set but no location name was given");
        }

        $distanceKey   = $this->getDistanceKey($distance, $baseSearchUriConfigDto);
        $gluedKeywords = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriConfigDto->isEncodeQuery(),
            $baseSearchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $queryParams[$baseSearchUriConfigDto->getPaginationNumberQueryParameter()] = $gluedKeywords;
        if (!empty($locationName)) {
            $queryParams[self::QUERY_LOCATION_NAME_PARAM] = $locationName;

            if (!empty($distanceKey)) {
                $queryParams[self::QUERY_DISTANCE_PARAM] = $distanceKey;
            }
        }

        $fullRequestUri = $baseSearchUriConfigDto->getBaseSearchUri()->getStandard()
                          . $pageNumber
                          . "?" . http_build_query($queryParams);

        return $fullRequestUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }
}