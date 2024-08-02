<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Pl;

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
 * Handles resolving data for service {@link https://www.jobs.pl/}, yaml file: `jobs.pl.yaml`
 */
class JobsPlResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
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
        $locationName           = $this->getLocationName($parameters);
        $queryParams            = [];
        $pageNumber             = $this->getPageNumber($parameters);
        $baseSearchUriConfigDto = $this->getBaseSearchUriConfigurationDto($parameters);
        $gluedKeywords          = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriConfigDto->isEncodeQuery(),
            $baseSearchUriConfigDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriConfigDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $searchUri = $searchUri . DIRECTORY_SEPARATOR;
        if (!empty($locationName)) {
            $normalisedLocation = SpecialCharacterHandler::escapeCharacters($locationName, Iso3166CountryCodeEnum::POLAND_3_DIGIT->value);
            $normalisedLocation = BaseLocationUrlHandlerService::handleSpacebarReplace(
                $baseSearchUriConfigDto->getLocationNameConfiguration(),
                $normalisedLocation
            );

            $searchUri .= $normalisedLocation . DIRECTORY_SEPARATOR;
        }

        $searchUri .= $gluedKeywords . ";k";

        $queryParams[$baseSearchUriConfigDto->getPaginationNumberQueryParameter()] = $pageNumber;

        $searchUri .= "?" . http_build_query($queryParams);

        return $searchUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }
}