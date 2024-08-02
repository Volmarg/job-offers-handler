<?php

namespace JobSearcher\Service\JobService\Resolver\DomHtml\Pl;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;
use JobSearcher\Service\EncodingService;
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
 * Handles resolving data for service {@link https://www.praca.pl/}, yaml file: `praca.pl`
 */
class PracaPlResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use LocationDistanceAwareTrait;
    use PageNumberAwareTrait;

    private const KEYWORD_EACH_WORD_SEPARATOR = ",";
    private const QUERY_KEYWORDS_PARAM = "p";

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
        $pageNumber             = $this->getPageNumber($parameters);
        $keywords               = $this->getKeywords($parameters);
        $locationName           = $this->getLocationName($parameters);
        $configurationDto       = $this->getBaseSearchUriConfigurationDto($parameters);
        $distance               = $this->getLocationDistance($parameters);
        $keywordsForQueryParam  = $this->buildKeywordsForQueryParam($keywords, $configurationDto);
        $keywordsForRequestUri  = $this->buildKeywordsForRequestUri($parameters);
        $baseRequestUri         = $this->buildBaseRequestUri(
            $keywordsForRequestUri,
            $locationName,
            $pageNumber,
            $searchUri
        );

        if (!empty($distance) && empty($locationName)) {
            throw new LogicException("Cannot build search uri, location distance is set but no location name was given");
        }

        $distanceKey = $this->getDistanceKey($distance, $configurationDto);

        $queryParams[self::QUERY_KEYWORDS_PARAM] = $keywordsForQueryParam;
        if (!empty($locationName)) {
            $queryParams[$configurationDto->getLocationNameConfiguration()->getQueryParameter()]     = $locationName;

            if (!empty($distanceKey)) {
                $queryParams[$configurationDto->getLocationDistanceConfiguration()->getQueryParameter()] = $distanceKey;
            }
        }

        $fullRequestUri = $baseRequestUri . "?" . http_build_query($queryParams);

        return $fullRequestUri;
    }

    public function init(): void
    {
        // TODO: Implement init() method.
    }

    /**
     * Builds list of keywords used in request uri.
     * See: `s-sprzedawca,handlowiec,testo,test_m-warszawa.html?p=sprzedawca-ii-handlowiec-ii-testo%20test&m=Warszawa&cr=100`,
     *       it's this part -> `sprzedawca-ii-handlowiec-ii-testo%20test`
     *
     * @param array $parameters
     *
     * @return string
     */
    private function buildKeywordsForRequestUri(array $parameters): string
    {
        $keywords = array_map(
            fn(string $kw) => mb_strtolower($kw),
            $this->getKeywords($parameters)
        );

        $imploded   = implode(" ", $keywords);
        $exploded   = explode(" ", $imploded);
        $normalized = implode(self::KEYWORD_EACH_WORD_SEPARATOR, $exploded);

        return $normalized;
    }

    /**
     * Builds list of keywords used in query params
     * See: `/s-sprzedawca,handlowiec,testo,test_m-warszawa.html`, it's this part -> `sprzedawca,handlowiec,testo,test`
     *
     * @param array                         $keywords
     * @param BaseSearchUriConfigurationDto $configurationDto
     *
     * @return string
     */
    private function buildKeywordsForQueryParam(array $keywords, BaseSearchUriConfigurationDto $configurationDto): string
    {
        $gluedKeywords = KeywordHandlerService::glueAll(
            $keywords,
            $configurationDto->isEncodeQuery(),
            $configurationDto->getMultipleKeyWordsSeparatorCharacter(),
            $configurationDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        return $gluedKeywords;
    }

    /**
     * Returns this part:
     * - with location: `/s-sprzedawca,handlowiec,testo,test_m-warszawa.html`
     * - without location: `/s-sprzedawca,handlowiec,testo,test.html`
     *
     * @param string      $keywordsForRequestUri
     * @param string|null $locationName
     * @param int         $pageNumber
     * @param string      $searchUri
     *
     * @return string
     */
    private function buildBaseRequestUri(
        string                        $keywordsForRequestUri,
        ?string                       $locationName,
        int                           $pageNumber,
        string                        $searchUri
    ): string
    {
        $requestUri = $searchUri . $keywordsForRequestUri;
        if (!empty($locationName)) {
            $normalizedLocationName = mb_strtolower(EncodingService::polishCharsToStandardChars($locationName));
            $requestUri             .= "_m-{$normalizedLocationName}";
        }

        $requestUri .= "_" . $pageNumber . ".html";

        return $requestUri;
    }

}