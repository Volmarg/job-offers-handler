<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Swe;

use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobSearch\UrlHandler\General\Location\BaseLocationUrlHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use Lingua\Service\SpecialCharacterHandler;

/**
 * Handles resolving data for service {@link https://jobb.blocket.se/}, yaml file: `jobb.blocket.se.yaml`
 */
class JobbBlocketSeResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;

    private const URI_NO_LOCATION_NAME = "/lediga-jobb-i-hela-sverige";
    private const URI_LOCATION_NAME_PREFIX = "/lediga-jobb-i-";

    private const PARAM_KEYWORDS = "ks";
    private const PARAM_KEYWORDS_PREFIX = "freetext.";
    private const PAGINATION_PARTIAL = "sida";

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
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

        $baseUri = self::URI_NO_LOCATION_NAME . DIRECTORY_SEPARATOR;
        if (!empty($locationName)) {
            $normalisedLocation = SpecialCharacterHandler::escapeCharacters(
                $locationName,
                Iso3166CountryCodeEnum::SWEDISH_3_DIGIT->value,
            );

            $normalisedLocation = BaseLocationUrlHandlerService::handleSpacebarReplace(
                $configurationDto->getLocationNameConfiguration(),
                $normalisedLocation
            );

            $baseUri = self::URI_LOCATION_NAME_PREFIX . mb_strtolower($normalisedLocation) . DIRECTORY_SEPARATOR;
        }

        $baseUri .= self::PAGINATION_PARTIAL . $pageNumber . DIRECTORY_SEPARATOR;

        $queryParams = [
          self::PARAM_KEYWORDS => self::PARAM_KEYWORDS_PREFIX . $gluedKeywords,
        ];

        $fullAbsoluteUri = $baseUri . "?" . http_build_query($queryParams);

        return $fullAbsoluteUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }

}