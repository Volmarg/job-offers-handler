<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Swe;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;

/**
 * Handles resolving data for service {@link https://jobbguru.se/}, yaml file: `jobbguru.se.yaml`
 */
class JobbGuruSeResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;
    use SearchUriAwareTrait;

    private const PARAM_KEYWORDS = "title";
    private const PARAM_LIMIT         = "limit";
    private const PARAM_LIMIT_DEFAULT = 20;

    private const LOCATION_PREFIX = "jobs-in-";

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        $searchUri        = $this->getSearchUri($parameters);
        $pageNumber       = $this->getPageNumber($parameters);
        $keywords         = $this->getKeywords($parameters);
        $locationName     = $this->getLocationName($parameters);
        $configurationDto = $this->getBaseSearchUriConfigurationDto($parameters);

        $gluedKeywords = KeywordHandlerService::glueAll(
            $keywords,
            $configurationDto->isEncodeQuery(),
            $configurationDto->getMultipleKeyWordsSeparatorCharacter(),
            $configurationDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $queryParams = [
          self::PARAM_KEYWORDS => $gluedKeywords,
          self::PARAM_LIMIT    => self::PARAM_LIMIT_DEFAULT,
          $configurationDto->getPaginationNumberQueryParameter() => $pageNumber
        ];

        $gluedPostfix = "";
        if (!empty($locationName)) {
            $gluedPostfix .= DIRECTORY_SEPARATOR
                             . self::LOCATION_PREFIX
                             . mb_strtolower($locationName)
                             . "--"
                             . mb_strtolower($locationName);
        }

        $preparedUri = $searchUri . $gluedPostfix . "?" . http_build_query($queryParams);

        return $preparedUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }

}