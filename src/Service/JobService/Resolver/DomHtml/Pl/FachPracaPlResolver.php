<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Pl;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobSearch\UrlHandler\General\Location\BaseLocationUrlHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationDistanceAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;

/**
 * Handles resolving data for service {@link https://fachpraca.pl/}, yaml file: `fachpraca.pl`
 */
class FachPracaPlResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use LocationDistanceAwareTrait;
    use PageNumberAwareTrait;

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
        $gluedKeywords          = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriConfigDto->isEncodeQuery(),
            $baseSearchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $searchUri = $searchUri
                   . DIRECTORY_SEPARATOR
                   . "q"
                   . DIRECTORY_SEPARATOR
                   . $gluedKeywords;

        $locationName = $this->getLocationName($parameters);
        if (!empty($locationName)) {
            $normalisedLocation = BaseLocationUrlHandlerService::handleSpacebarReplace(
                $baseSearchUriConfigDto->getLocationNameConfiguration(),
                $locationName
            );

            $searchUri .= DIRECTORY_SEPARATOR
                          . "l"
                          . DIRECTORY_SEPARATOR
                          . $normalisedLocation
                          . DIRECTORY_SEPARATOR;
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