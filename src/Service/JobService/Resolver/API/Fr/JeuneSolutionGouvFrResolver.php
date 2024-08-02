<?php

namespace JobSearcher\Service\JobService\Resolver\API\Fr;

use Exception;
use GeoTool\Service\CountryCode\FrenchDivisionCode;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\DetailPageAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;

/**
 * Handles resolving data for service {@link https://www.1jeune1solution.gouv.fr/}, yaml file: `1jeune1solution.gouv.fr.yaml`
 */
class JeuneSolutionGouvFrResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;
    use DetailPageAwareTrait;

    private const QUERY_PARAM_LOCATION_CODE = "codeLocalisation";
    private const QUERY_PARAM_LOCATION_TYPE = "typeLocalisation";
    private const QUERY_PARAM_LOCATION_NAME = "nomLocalisation";
    private const QUERY_PARAM_KEYWORDS = "motCle";

    private const LOCATION_TYPE_DEPARTMENT = "DEPARTEMENT";

    /**
     * @return FrenchDivisionCode
     */
    private function getFrenchDivisionCodeService(): FrenchDivisionCode
    {
        return $this->kernel->getContainer()->get(FrenchDivisionCode::class);
    }

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     * @throws Exception
     */
    public function buildSearchUri(array $parameters): string
    {
        $queryParams      = [];
        $baseSearchUriDto = $this->getBaseSearchUriConfigurationDto($parameters);
        $locationName     = $this->getLocationName($parameters);
        $pageNumber       = $this->getPageNumber($parameters);
        $gluedKeywords    = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriDto->isEncodeQuery(),
            $baseSearchUriDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $queryParams[self::QUERY_PARAM_KEYWORDS] = $gluedKeywords;
        if (!empty($pageNumber)) {
            $queryParams[$baseSearchUriDto->getPaginationNumberQueryParameter()] = $pageNumber;
        }

        if (!empty($locationName)) {
            $divisionNumber = $this->getFrenchDivisionCodeService()->getDivNumber($locationName);
            if (empty($divisionNumber)) {
                throw new Exception("No division number found for location: {$locationName}, this might be fine, maybe user added wrong location name");
            }

            $queryParams[self::QUERY_PARAM_LOCATION_TYPE] = self::LOCATION_TYPE_DEPARTMENT;
            $queryParams[self::QUERY_PARAM_LOCATION_NAME] = $locationName;
            $queryParams[self::QUERY_PARAM_LOCATION_CODE] = $divisionNumber;
        }

        return $this->getSearchUri($parameters)
               . $this->getBuildId()
               . DIRECTORY_SEPARATOR
               . "emplois.json"
               . "?"
               . http_build_query($queryParams);
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        // TODO: Implement init() method.
    }

    /**
     * The buildId is fetched each time. It's suspected that it's changing daily so
     * if user would start search 23:59, then next day token might be different.
     *
     * @return string
     * @throws Exception
     */
    private function getBuildId(): string
    {
        // This is lightweight page so extracting it will be fast
        $url        = "https://www.1jeune1solution.gouv.fr/plan-du-site";
        $tokenRegex = '#__NEXT_DATA__.*buildId["\']{1}([ ]{0,}):([ ]{0,})["\']{1}(?<BUILD_ID>.*)["\']{1}([ ]{0,}),#m';

        $crawlerConfig = new CrawlerConfigurationDto($url, CrawlerService::CRAWLER_ENGINE_GOUTTE);
        $crawlerConfig->setWithProxy(EnvReader::isProxyEnabled());

        $crawlerService = $this->kernel->getContainer()->get(CrawlerService::class);
        $crawler       = $crawlerService->crawl($crawlerConfig);

        $html = $crawler->html();
        if (empty($html)) {
            throw new Exception("Could not get html content of page used for getting buildId: " . $url);
        }

        preg_match($tokenRegex, $html, $matches);
        $buildId = $matches['BUILD_ID'] ?? null;

        if (empty($buildId)) {
            throw new Exception("Could not extract build id from html!");
        }

        return $buildId;
    }

}