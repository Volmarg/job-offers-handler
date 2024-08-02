<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder\Api;

use Exception;
use JobSearcher\DTO\JobSearch\Api\HeaderDto;
use JobSearcher\DTO\JobSearch\Api\RawBodyParametersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\DetailPageConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\JsonStructureConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\SearchUriConfigurationDto;
use JobSearcher\Exception\JobServiceCallableResolverException;
use JobSearcher\Service\JobService\ConfigurationBuilder\AbstractConfigurationBuilder;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;

/**
 * Contain all the job search configuration.
 * These are then used for fetching the job offers details by fetching content directly from DOM HTML
 *
 * Info: the configuration validation should not be cleaned up, even if some settings are optional it will be too hard
 * to track which options are available at all
 */
class ApiConfigurationBuilder extends AbstractConfigurationBuilder implements ApiConfigurationBuilderInterface
{
    /**
     * @var MainConfigurationDto[]
     */
    private array $jobSearchConfigurations = [];

    /**
     * {@inheritDoc}
     *
     * @return string
     * @throws Exception
     */
    protected function getConfigurationFilesFolderName(): string
    {
        return "api";
    }

    /**
     * @param string|null $country
     *
     * @return MainConfigurationDto[]
     */
    public function getJobSearchConfigurations(?string $country = null): array
    {
        if (empty($country)) {
            return $this->jobSearchConfigurations;
        }

        $filteredConfigurations = [];
        foreach ($this->jobSearchConfigurations as $jobSearchConfiguration) {
            if ($jobSearchConfiguration->getSupportedCountry() === $country) {
                $filteredConfigurations[$jobSearchConfiguration->getConfigurationName()] = $jobSearchConfiguration;
            }
        }

        return $filteredConfigurations;
    }

    /**
     * Will build array of {@see \JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto} and fill the property {@see ApiConfigurationBuilder::$jobSearchConfigurations}
     *
     * @throws Exception
     */
    public function loadAllConfigurations(): void
    {

        $allConfigurationReadingResults = $this->readConfigurationFilesAndValidateTheirContent();
        foreach($allConfigurationReadingResults as $configurationName => $configurationReadingResult){
            $mainConfigurationDto       = $this->buildMainConfigurationDto($configurationReadingResult->getConfigurationContent(), $configurationName);
            $searchUriConfigurationDto  = $this->buildSearchUriConfigurationDto($configurationReadingResult->getConfigurationContent());
            $detailPageConfigurationDto = $this->buildDetailPageConfigurationDto($configurationReadingResult->getConfigurationContent());
            $jsonStructureDto           = $this->buildJsonStructureDto($configurationReadingResult->getConfigurationContent());

            $mainConfigurationDto->setSearchUriConfigurationDto($searchUriConfigurationDto);
            $mainConfigurationDto->setDetailPageConfigurationDto($detailPageConfigurationDto);
            $mainConfigurationDto->setJsonStructureConfigurationDto($jsonStructureDto);
            $mainConfigurationDto->setSupportedCountry($configurationReadingResult->getSupportedCountry());

            $this->jobSearchConfigurations[$configurationName] = $mainConfigurationDto;
        }

    }

    /**
     * Will use the parsed array and ensure that it's structure is valid
     * @throws Exception
     */
    protected function validateConfiguration(array $parsedFile): void
    {
        # General
            $crawlDelay = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile,self::KEY_CRAWL_DELAY);
            if (!empty($crawlDelay)) {
                $this->validateCrawlingDelay($crawlDelay, $parsedFile);
            }

        # Search Uri
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_SEARCH_URI_METHOD);

        # Detail page
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_DETAIL_PAGE_METHOD);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_URL);

        # Pagination Json structure
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_ALL_JOBS_DATA);

            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_COMPANY_NAME);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_POSTED_DATE_TIME);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_DETAIL_IDENTIFIER_FIELD);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_DETAIL_MORE_INFORMATION);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_TITLE);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_DESCRIPTION);

            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_LOCATION_TYPE);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_LOCATION_SINGLE_ENTRY_PATH);
            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_JSON_STRUCTURE_JOB_LOCATION_ARRAY_STRUCTURE_PATH);
    }

    /**
     * Will set the {@see DetailPageConfigurationDto}
     *
     * @param array $parsingResult
     *
     * @return DetailPageConfigurationDto
     * @throws JobServiceCallableResolverException
     */
    private function buildDetailPageConfigurationDto(array $parsingResult): DetailPageConfigurationDto
    {
        $baseDto                          = $this->buildBaseDetailPageConfigurationDto($parsingResult);
        $searchDetailPageConfigurationDto = DetailPageConfigurationDto::buildFromBaseDto($baseDto);

        $requestRawBodyDtos  = $this->buildRawBodyParametersDtos($parsingResult, self::KEY_DETAIL_PAGE_RAW_BODY_PARAMS);
        $headersDtos         = $this->buildHeadersDto($parsingResult, self::KEY_DETAIL_PAGE_HEADERS);
        $method              = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_DETAIL_PAGE_METHOD);
        $identifierPlacement = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_DETAIL_PAGE_IDENTIFIER_PLACEMENT) ?? null;
        $hostUriGlueString   = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_HOST_GLUE_STRING) ?? null;
        $resolver            = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_DETAIL_PAGE_DATA_RESOLVER);

        $isIdentifierAfterSlash = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_DETAIL_PAGE_IDENTIFIER_IS_AFTER_SLASH) ?? true;

        $descriptionRemovedElementsSelectors = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString(
            $parsingResult, self::KEY_DETAIL_PAGE_DESCRIPTION_REMOVED_ELEMENTS_SELECTORS) ?? [];

        $searchDetailPageConfigurationDto->setHostUriGlueString($hostUriGlueString);
        $searchDetailPageConfigurationDto->setRequestRawBody($requestRawBodyDtos);
        $searchDetailPageConfigurationDto->setIdentifierPlacement($identifierPlacement);
        $searchDetailPageConfigurationDto->setIdentifierAfterSlash($isIdentifierAfterSlash);
        $searchDetailPageConfigurationDto->setRequestHeaders($headersDtos);
        $searchDetailPageConfigurationDto->setMethod($method);
        $searchDetailPageConfigurationDto->setOfferDataResolver($resolver);
        $searchDetailPageConfigurationDto->setDescriptionRemovedElementsSelectors($descriptionRemovedElementsSelectors);

        $searchDetailPageConfigurationDto->validateSelf();

        return $searchDetailPageConfigurationDto;
    }

    /**
     * Will set the {@see SearchUriConfigurationDto}
     *
     * @param array $parsingResult
     * @return SearchUriConfigurationDto
     */
    private function buildSearchUriConfigurationDto(array $parsingResult): SearchUriConfigurationDto
    {
        $baseSearchUriConfigurationDto = $this->buildBaseSearchUriConfigurationDto($parsingResult);
        $searchUriConfigurationDto     = SearchUriConfigurationDto::buildFromBaseDto($baseSearchUriConfigurationDto);

        $requestRawBodyDtos = $this->buildRawBodyParametersDtos($parsingResult, self::KEY_SEARCH_URI_RAW_BODY_PARAMS);
        $headersDtos        = $this->buildHeadersDto($parsingResult, self::KEY_SEARCH_URI_HEADERS);
        $scrapEngine        = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_SEARCH_URI_SCRAP_ENGINE) ?? null;
        $method             = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_SEARCH_URI_METHOD);

        $searchUriConfigurationDto->setRequestRawBody($requestRawBodyDtos);
        $searchUriConfigurationDto->setRequestHeaders($headersDtos);
        $searchUriConfigurationDto->setScrapEngine($scrapEngine);
        $searchUriConfigurationDto->setMethod($method);

        return $searchUriConfigurationDto;
    }

    /**
     * Will build {@see MainConfigurationDto} from yaml parsed data
     *
     * @param array  $parsingResult
     * @param string $configurationName
     *
     * @return MainConfigurationDto
     */
    private function buildMainConfigurationDto(array $parsingResult, string $configurationName): MainConfigurationDto
    {
        $crawlDelay = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_CRAWL_DELAY) ?? self::MINIMAL_CRAWL_DELAY;

        $baseDto              = $this->buildBaseMainConfigurationDto($parsingResult, $configurationName);
        $mainConfigurationDto = MainConfigurationDto::buildFromBaseDto($baseDto);
        $mainConfigurationDto->setCrawlDelay($crawlDelay);

        return $mainConfigurationDto;
    }

    /**
     * Will return array of {@see RawBodyParametersDto}
     *
     * @param array      $parsingResult
     * @param string     $searchedString
     * @param array|null $params
     *
     * @return RawBodyParametersDto[]
     */
    private function buildRawBodyParametersDtos(array $parsingResult, string $searchedString, ?array $params = null): array
    {
        $arrayOfDto = [];
        if( is_null($params) ){
            $params = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, $searchedString) ?? [];
        }

        foreach($params as $param){
            $name     = $param[self::KEY_NAME];
            $value    = $param[self::KEY_VALUE];
            $children = $param[self::KEY_CHILDREN];

            $usedChildren = [];
            if( !empty($children) ){
                $usedChildren = $this->buildRawBodyParametersDtos([], $searchedString, $children);
            }

            $dto = new RawBodyParametersDto(
                $name,
                $value,
                $usedChildren,
            );

            $arrayOfDto[] = $dto;
        }

        return $arrayOfDto;
    }

    /**
     * Will return array of {@see HeaderDto}
     *
     * @param array  $parsingResult
     * @param string $searchedKey
     *
     * @return HeaderDto[]
     */
    private function buildHeadersDto(array $parsingResult, string $searchedKey): array
    {
        $arrayOfDto = [];

        $params = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, $searchedKey) ?? [];
        foreach($params as $param){
            $name  = $param[self::KEY_NAME];
            $value = $param[self::KEY_VALUE];

            $arrayOfDto[] = new HeaderDto($name, $value);
        }

        return $arrayOfDto;
    }

    /**
     * Will build {@see JsonStructureConfigurationDto}
     *
     * @param array $parsingResult
     * @return JsonStructureConfigurationDto
     */
    private function buildJsonStructureDto(array $parsingResult): JsonStructureConfigurationDto
    {
        $jsonStructureConfigurationDto = new JsonStructureConfigurationDto();
        $allJobsData                   = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_ALL_JOBS_DATA);

        $url                        = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_URL);
        $jobTitle                   = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_TITLE);
        $companyName                = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_COMPANY_NAME);
        $jobDescription             = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_DESCRIPTION);
        $jobPostedDateTime          = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_POSTED_DATE_TIME);
        $jobDetailMoreInformation   = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_DETAIL_MORE_INFORMATION);
        $detailPageIdentifierField  = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_DETAIL_IDENTIFIER_FIELD) ?? '';

        $locationType               = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_LOCATION_TYPE);
        $locationSingleEntryPath    = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_LOCATION_SINGLE_ENTRY_PATH);
        $locationArrayStructurePath = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_JSON_STRUCTURE_JOB_LOCATION_ARRAY_STRUCTURE_PATH);

        $jsonStructureConfigurationDto->setCompanyName($companyName);
        $jsonStructureConfigurationDto->setJobPostedDateTime($jobPostedDateTime);
        $jsonStructureConfigurationDto->setDetailPageIdentifierField($detailPageIdentifierField);
        $jsonStructureConfigurationDto->setJobTitle($jobTitle);
        $jsonStructureConfigurationDto->setJobDescription($jobDescription);
        $jsonStructureConfigurationDto->setAllJobsData($allJobsData);
        $jsonStructureConfigurationDto->setJobDetailMoreInformation($jobDetailMoreInformation);
        $jsonStructureConfigurationDto->setJobOfferUrl($url);

        $jsonStructureConfigurationDto->setLocationType($locationType);
        $jsonStructureConfigurationDto->setLocationSingleEntryPath($locationSingleEntryPath);
        $jsonStructureConfigurationDto->setLocationArrayStructurePath($locationArrayStructurePath);

        return $jsonStructureConfigurationDto;
    }
}