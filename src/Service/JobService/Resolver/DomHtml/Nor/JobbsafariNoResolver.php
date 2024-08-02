<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Nor;

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
 * Handles resolving data for service {@link https://www.jobbsafari.no/}, yaml file: `jobbsafari.no.yaml`
 */
class JobbsafariNoResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;
    use SearchUriAwareTrait;

    private const PARAM_KEYWORDS = "q";

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
          $configurationDto->getPaginationNumberQueryParameter() => $pageNumber
        ];

        if (!empty($locationName)) {
            $normalisedLocation = SpecialCharacterHandler::escapeCharacters(
                $locationName,
                Iso3166CountryCodeEnum::NORWAY_3_DIGIT->value,
                false
            );

            $normalisedLocation = BaseLocationUrlHandlerService::handleSpacebarReplace(
                $configurationDto->getLocationNameConfiguration(),
                $normalisedLocation
            );

            $searchUri .= DIRECTORY_SEPARATOR . mb_strtolower($normalisedLocation);
        }

        $preparedUri = $searchUri . "?" . http_build_query($queryParams);

        return $preparedUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }

}