<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Esp;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobSearch\UrlHandler\General\Location\BaseLocationUrlHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MainConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;

/**
 * Handles resolving data for service {@link https://www.elempleo.com/}, yaml file: `elempleo.com.yaml`
 */
class ElempleoComResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use MainConfigurationDtoAwareTrait;

    private const KEYWORDS = "trabajo";

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        $queryParams            = [];
        $searchUri              = $this->getSearchUri($parameters);
        $locationName           = $this->getLocationName($parameters);
        $baseSearchUriConfigDto = $this->getBaseSearchUriConfigurationDto($parameters);
        $gluedKeywords          = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriConfigDto->isEncodeQuery(),
            $baseSearchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $searchUri = $searchUri . DIRECTORY_SEPARATOR;
        if (!empty($locationName)) {
            $locationName = BaseLocationUrlHandlerService::handleSpacebarReplace(
                $baseSearchUriConfigDto->getLocationNameConfiguration(),
                $locationName
            );

            // it's just that if location is like "x de something" then the location in url must be without that "de" thing
            $locationName = str_replace(" de ", "", $locationName);

            $searchUri .= mb_strtolower($locationName);
        }

        $queryParams[self::KEYWORDS] = $gluedKeywords;

        $searchUri .= "?" . http_build_query($queryParams);

        return $searchUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }
}