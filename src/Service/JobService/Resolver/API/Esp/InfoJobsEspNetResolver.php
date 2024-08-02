<?php

namespace JobSearcher\Service\JobService\Resolver\API\Esp;

use Exception;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\DetailPageAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MainConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use JobSearcher\Service\Validation\ValidatorService;
use WebScrapperBundle\Constants\UserAgentConstants;
use WebScrapperBundle\Service\Request\Guzzle\GuzzleService;
use WebScrapperBundle\Service\ScrapEngine\CliCurlScrapEngine;
use WebScrapperBundle\Service\ScrapEngine\ScrapEngineInterface;

/**
 * Handles resolving data for service {@link https://www.infojobs.net/}, yaml file: `infojobs.net.yaml`
 *
 * There was an attempt to get the detailed description of offer from detail page, but that fails to hard,
 * thus decided to only extract the description from the pagination (which is smaller, but still has some significant
 * data - significant enough to take this as an actual description
 *
 * {@see CliCurlScrapEngine} is a must, guzzle fails way more often, no idea why.
 */
class InfoJobsEspNetResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;
    use DetailPageAwareTrait;
    use MainConfigurationDtoAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;

    private const PARAM_KEYWORD = "keyword";
    private const PARAM_SORT_BY = "sortBy";
    private const PARAM_ONLY_FOREIGN_COUNTRY = 'onlyForeignCountry';
    private const PARAM_SINCE_DATE = "sinceDate";
    private const PARAM_PROVINCE_IDS = "provinceIds";

    private const SORT_BY_TYPE_RELEVANCE = "RELEVANCE";
    private const ONLY_FOREIGN_COUNTRY_VALUE_FALSE = "false";
    private const SINCE_DATE_VALUE_ANY = "ANY";

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
        $baseSearchUriDto = $this->getBaseSearchUriConfigurationDto($parameters);
        $pageNumber       = $this->getPageNumber($parameters);
        $locationName     = $this->getLocationName($parameters);

        $gluedKeywords = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriDto->isEncodeQuery(),
            $baseSearchUriDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $queryParams = [
            self::PARAM_KEYWORD              => $gluedKeywords,
            self::PARAM_SORT_BY              => self::SORT_BY_TYPE_RELEVANCE,
            self::PARAM_ONLY_FOREIGN_COUNTRY => self::ONLY_FOREIGN_COUNTRY_VALUE_FALSE,
            self::PARAM_SINCE_DATE           => self::SINCE_DATE_VALUE_ANY,
            $baseSearchUriDto->getPaginationNumberQueryParameter() => $pageNumber,
        ];

        if (!empty($locationName)) {
            $provinces  = $this->getProvinces($parameters);
            $provinceId = $provinces[mb_strtolower($locationName)] ?? null;
            if (empty($provinceId)) {
                throw new Exception("No province found for location name: " . $locationName);
            }

            $queryParams[self::PARAM_PROVINCE_IDS] = $provinceId;
        }

        $uri = $this->getSearchUri($parameters) . "?" . http_build_query($queryParams);

        return $uri;
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        // TODO: Implement init() method.
    }

    /**
     * First need to fetch provinces data, thus dummy request that's going to be used to extract ids
     * from it. These id's is something specific for the service, this is not some "standardized data"
     *
     * @param array $parameters
     *
     * @return array where `key` is the province name and `value` is the id
     *
     * @throws Exception
     */
    private function getProvinces(array $parameters): array
    {
        $validator       = $this->kernel->getContainer()->get(ValidatorService::class);
        $cliCurlScrapper = $this->kernel->getContainer()->get(CliCurlScrapEngine::class);

        $mainConfigDto = $this->getMainConfigurationDto($parameters);
        $uriQueryParams = [
            self::PARAM_KEYWORD => "",
            $mainConfigDto->getSearchUriConfigurationDto()->getPaginationNumberQueryParameter() => 1,
        ];

        $uri     = $mainConfigDto->getSearchUriConfigurationDto()->getBaseSearchUri()->getStandard() . "?" . http_build_query($uriQueryParams);
        $fullUrl = "{$mainConfigDto->getHost()}{$uri}";

        /**
         * {@see GuzzleService} is returning 403, thus using {@see CliCurlScrapEngine}
         */
        $json = $cliCurlScrapper->scrap($fullUrl, [
            ScrapEngineInterface::CONFIGURATION_HEADERS => [
                ScrapEngineInterface::CONFIGURATION_USER_AGENT => UserAgentConstants::CHROME_114,
                ScrapEngineInterface::CONFIGURATION_USE_PROXY  => true,
            ]
        ]);

         if (!$validator->validateJson($json)) {
            throw new Exception("Response for getting the provinces is not a json for info jobs net (spanish)!");
        }

        $data         = json_decode($json, true);
        $aggregation  = $data['aggregation'] ?? [];
        $provinces    = $aggregation['province'] ?? [];
        $returnedData = [];
        foreach ($provinces as $province) {
            $name = $province['label'] ?? null;
            $id   = $province['value'] ?? null;

            if (!empty($name) && !empty($id)) {
                $returnedData[mb_strtolower($name)] = $id;
            }
        }

        if (empty($returnedData)) {
            throw new Exception("Could not extract the provinces for jobs net (spanish). Aggregation data: " . json_encode($aggregation));
        }

        return $returnedData;
    }
}