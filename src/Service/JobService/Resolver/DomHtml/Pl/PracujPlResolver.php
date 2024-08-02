<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Pl;

use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationDistanceAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use Lingua\Service\SpecialCharacterHandler;
use LogicException;

/**
 * Handles resolving data for service {@link https://www.pracuj.pl/}, yaml file: `pracuj.pl`
 */
class PracujPlResolver extends JobServiceResolver
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
        $distance               = $this->getLocationDistance($parameters);
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

        $searchUri = $searchUri
                   . DIRECTORY_SEPARATOR
                   . $gluedKeywords
                   . ";kw";


        if (!empty($distance) && empty($locationName)) {
            throw new LogicException("Cannot build search uri, location distance is set but no location name was given");
        }

        $distanceKey = $this->getDistanceKey($distance, $baseSearchUriConfigDto);

        if (!empty($locationName)) {
            $normalisedLocation = SpecialCharacterHandler::escapeCharacters($locationName, Iso3166CountryCodeEnum::POLAND_3_DIGIT->value);
            $searchUri .= DIRECTORY_SEPARATOR
                          . $normalisedLocation
                          . ";wp";

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