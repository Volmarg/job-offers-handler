<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Pl;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;

/**
 * Handles resolving data for service {@link https://theprotocol.it/}, yaml file: `theprotocol.it`
 */
class TheProtocolItResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
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

        $searchUri = $searchUri . DIRECTORY_SEPARATOR . $gluedKeywords . ";t";

        $queryParams[$baseSearchUriConfigDto->getPaginationNumberQueryParameter()] = $pageNumber;

        $searchUri .= "?" . http_build_query($queryParams);

        return $searchUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }
}