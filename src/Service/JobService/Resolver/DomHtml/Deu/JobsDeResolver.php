<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Deu;

use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobSearch\UrlHandler\General\Location\BaseLocationUrlHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use Lingua\Service\SpecialCharacterHandler;

/**
 * Handles resolving data for service {@link https://jobs.de/}, yaml file: `jobs.de.yaml`
 */
class JobsDeResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;

    private const LOCATION_PREFIX_STRING = "jobs-in-";
    private const PARAM_KEYWORDS = "title";
    private const PARAM_LIMIT_PER_PAGE = "limit";
    private const PARAM_SORT = "orderBy";

    private const SORT_TYPE_RELEVANCE = "relevance";
    private const RESULTS_LIMIT_PER_PAGE = 20;

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
        $baseSearchUriConfigDto = $this->getBaseSearchUriConfigurationDto($parameters);

        $gluedKeywords = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriConfigDto->isEncodeQuery(),
            $baseSearchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $queryParams[$baseSearchUriConfigDto->getPaginationNumberQueryParameter()] = $pageNumber;
        $queryParams[self::PARAM_KEYWORDS]                                         = $gluedKeywords;
        $queryParams[self::PARAM_LIMIT_PER_PAGE]                                   = self::RESULTS_LIMIT_PER_PAGE;
        $queryParams[self::SORT_TYPE_RELEVANCE]                                    = self::SORT_TYPE_RELEVANCE;

        $locationString = "";
        if (!empty($locationName)) {
            $locationString = SpecialCharacterHandler::escapeCharacters($locationName, Iso3166CountryCodeEnum::GERMANY_3_DIGIT->value);
            $locationString = BaseLocationUrlHandlerService::handleSpacebarReplace(
                $baseSearchUriConfigDto->getLocationNameConfiguration(),
                $locationString
            );

            $locationString = DIRECTORY_SEPARATOR . $this->buildLocationPart($locationString);
        }

        $fullRequestUri = $baseSearchUriConfigDto->getBaseSearchUri()->getStandard()
                          . $locationString
                          . "?" . http_build_query($queryParams);

        return $fullRequestUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }

    /**
     * @param string $locationName
     *
     * @return string
     */
    private function buildLocationPart(string $locationName): string
    {
        $lowerCaseLocation = mb_strtolower($locationName);
        return self::LOCATION_PREFIX_STRING . "{$lowerCaseLocation}--{$lowerCaseLocation}";
    }
}