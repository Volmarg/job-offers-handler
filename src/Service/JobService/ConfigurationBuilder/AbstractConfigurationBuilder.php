<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseDetailPageConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation\BaseLocationDistance;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriLocation\BaseLocationName;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseMainConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\ConfigurationReadingResult;
use JobSearcher\Service\JobService\ConfigurationBuilder\Common\SearchUriConfigurationBuilder;
use JobSearcher\Service\JobService\ConfigurationBuilder\Constants\LocationSearchUriConstants;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use Exception;
use LogicException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Handles building the base configuration dto for any type of job searching method
 */
abstract class AbstractConfigurationBuilder
{
    const KEY_SEARCH_URI_STRUCTURE              = "search_uri.structure";
    const KEY_SEARCH_URI_BASE_HOST              = "search_uri.base_host";
    const KEY_SEARCH_URI_KEYWORDS_PLACEMENT     = "search_uri.keywords_placement";
    const KEY_SEARCH_URI_ENCODE_QUERY           = "search_uri.encode_query";
    const KEY_SEARCH_URI_RESOLVER               = "search_uri.resolver";

    const KEY_PAGINATION_START_VALUE                        = "pagination.start_value";
    const KEY_PAGINATION_INCREMENT_VALUE                    = "pagination.increment_value";
    const KEY_PAGINATION_FIRST_PAGE_VALUE                      = "pagination.first_page_value";
    const KEY_PAGINATION_MULTIPLE_KEYWORDS_SEPARATOR_CHARACTER = "pagination.multiple_keywords_separator_character";

    // some service require the spacebar in such keywords like "cookie baker" to be replaced into for example "cookie-baker"
    public const KEY_PAGINATION_SPACEBAR_IN_KEYWORD_WORDS_REPLACE_CHARACTER = "pagination.pagination_spacebar_in_keyword_words_replace_character";

    const KEY_PAGINATION_PAGE_NUMBER_QUERY_PARAMETER_NAME      = "pagination.page_number_query_parameter_name";

    const KEY_DETAIL_PAGE_BASE_HOST = "detail_page.base_host";
    const KEY_DETAIL_PAGE_BASE_URI  = "detail_page.base_uri";

    const KEY_CONFIGURATION_NAME    = "configuration.name";
    const KEY_CONFIGURATION_ENABLED = "configuration.enabled";

    const KEY_HOST                                      = "host";
    const KEY_LINKS_DETAIL_PAGE_EXCLUDED_REGEX_PATTERNS = "links.detail_page.excluded_regex_patterns";
    const KEY_LINKS_DETAIL_REPLACE_REGEX_RULES          = "links.detail_page.replace_regex_rules";

    /**
     * Minimal crawling delay in seconds. That's required due to the fact there is generally some law which
     * could be used against this project.
     *
     * It's something like:
     * "Using server resources to the point where it cannot function normally, causing user connection to break etc."
     *
     * So it's "ddos mini". That's why adding the minimal delay - to reduce abusing the crawled server resources
     *
     * This value is in MILLISECONDS
     */
    protected const MINIMAL_CRAWL_DELAY = 2500;

    /**
     * @var KernelInterface $kernel
     */
    protected KernelInterface $kernel;

    /**
     * @var SearchUriConfigurationBuilder $searchUriConfigurationBuilder
     */
    private SearchUriConfigurationBuilder $searchUriConfigurationBuilder;

    /**
     * Will return the name of the folder inside of {@see ConfigurationReader::KEY_JOB_SERVICES_CONFIGURATIONS_FILES_FOLDER_NAME}
     * which contains the configuration files
     *
     * @return string
     */
    abstract protected function getConfigurationFilesFolderName(): string;

    /**
     * Will validate the configuration
     *
     * @param array $parsedConfigurationFile
     */
    abstract protected function validateConfiguration(array $parsedConfigurationFile): void;

    /**
     * @param KernelInterface     $kernel
     * @param ConfigurationReader $configurationReader
     */
    public function __construct(
        KernelInterface                      $kernel,
        private readonly ConfigurationReader $configurationReader
    )
    {
        $this->kernel                        = $kernel;
        $this->searchUriConfigurationBuilder = $kernel->getContainer()->get(SearchUriConfigurationBuilder::class);
    }

    /**
     * Will load the configuration files, parse them into array and return these arrays
     *
     * @return ConfigurationReadingResult[]
     * @throws Exception
     */
    protected function readConfigurationFilesAndValidateTheirContent(): array
    {
        $allConfigurations          = [];
        $configurationFilesForTypes = $this->configurationReader->getAllConfigurationFilesPaths();
        foreach ($configurationFilesForTypes as $filePaths) {
            foreach ($filePaths as $filePath) {

                if (
                        !str_contains($filePath, $this->configurationReader->getConfigurationTypeMatchPattern($this->getConfigurationFilesFolderName()))
                    ||
                        (
                                !str_contains($filePath, "yaml")
                            &&  !str_contains($filePath, "yml")
                      )
                ) {
                    continue;
                }

                $yamlFileContent = file_get_contents($filePath);
                $parsingResult   = Yaml::parse($yamlFileContent, Yaml::PARSE_CONSTANT);
                $parsingResult   = $this->handleSharedConfig($parsingResult);
                $this->validateBaseConfigurationStructure($parsingResult);
                $this->validateConfiguration($parsingResult);

                $configurationName = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_CONFIGURATION_NAME);
                $isEnabled         = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, self::KEY_CONFIGURATION_ENABLED);

                if (!$isEnabled) {
                    continue;
                }

                $countryName   = $this->configurationReader->getCountryForConfigurationPath($filePath);
                $readingResult = new ConfigurationReadingResult($filePath, $countryName, $parsingResult);

                $allConfigurations[$configurationName] = $readingResult;
            }
        }

        return $allConfigurations;
    }

    /**
     * Will build and return {@see BaseMainConfigurationDto}
     *
     * @param array  $parsedFile
     * @param string $configurationName
     *
     * @return BaseMainConfigurationDto
     */
    protected function buildBaseMainConfigurationDto(array $parsedFile, string $configurationName): BaseMainConfigurationDto
    {
        $baseMainConfigurationDto = new BaseMainConfigurationDto();

        $host                           = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_HOST);
        $detailPageLinkExcludedPatterns = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_LINKS_DETAIL_PAGE_EXCLUDED_REGEX_PATTERNS) ?? [];
        $replaceRegexRules              = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_LINKS_DETAIL_REPLACE_REGEX_RULES) ?? [];

        $baseMainConfigurationDto->setHost($host);
        $baseMainConfigurationDto->setDetailPageLinkExcludedPatterns($detailPageLinkExcludedPatterns);
        $baseMainConfigurationDto->setDetailPageLinkReplaceRegexRules($replaceRegexRules);
        $baseMainConfigurationDto->setConfigurationName($configurationName);

        return $baseMainConfigurationDto;
    }

    /**
     * Will build the {@see BaseSearchUriConfigurationDto}
     */
    protected function buildBaseSearchUriConfigurationDto(array $parsedFile): BaseSearchUriConfigurationDto
    {
        $paginationFirstPageValue           = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_PAGINATION_FIRST_PAGE_VALUE) ?? null;
        $paginationStartValue               = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_PAGINATION_START_VALUE) ?? null;
        $paginationIncrementValue           = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_PAGINATION_INCREMENT_VALUE) ?? null;
        $paginationNumberQueryParameter     = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_PAGINATION_PAGE_NUMBER_QUERY_PARAMETER_NAME);
        $multipleKeyWordsSeparatorCharacter = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_PAGINATION_MULTIPLE_KEYWORDS_SEPARATOR_CHARACTER);
        $searchUriBaseHost                  = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_SEARCH_URI_BASE_HOST);
        $structure                          = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_SEARCH_URI_STRUCTURE) ?? [];
        $encodeQuery                        = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_SEARCH_URI_ENCODE_QUERY) ?? false;
        $resolver                           = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_SEARCH_URI_RESOLVER) ?? null;
        $keywordsPlacement                  = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_SEARCH_URI_KEYWORDS_PLACEMENT) ?? null;

        $paginationSpacebarInKeywordWordsReplaceCharacter = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString(
            $parsedFile,
            self::KEY_PAGINATION_SPACEBAR_IN_KEYWORD_WORDS_REPLACE_CHARACTER
        ) ?? null;

        if(
                empty($resolver)
            &&  empty($keywordsPlacement)
        ){
          throw new LogicException("Both resolver and keywords placement configuration are empty. One of these must be set!");
        }

        $searchUriBaseDto              = $this->searchUriConfigurationBuilder->buildBaseSearchUri($parsedFile);
        $baseSearchUriConfigurationDto = new BaseSearchUriConfigurationDto();

        $this->buildSearchUriLocationConfiguration($parsedFile, $baseSearchUriConfigurationDto);

        $baseSearchUriConfigurationDto->setPaginationFirstPageValue($paginationFirstPageValue);
        $baseSearchUriConfigurationDto->setPaginationStartValue($paginationStartValue);
        $baseSearchUriConfigurationDto->setPaginationIncrementValue($paginationIncrementValue);
        $baseSearchUriConfigurationDto->setPaginationNumberQueryParameter($paginationNumberQueryParameter);
        $baseSearchUriConfigurationDto->setMultipleKeyWordsSeparatorCharacter($multipleKeyWordsSeparatorCharacter);
        $baseSearchUriConfigurationDto->setPaginationSpacebarInKeywordWordsReplaceCharacter($paginationSpacebarInKeywordWordsReplaceCharacter);
        $baseSearchUriConfigurationDto->setBaseSearchUri($searchUriBaseDto);
        $baseSearchUriConfigurationDto->setSearchUriBaseHost($searchUriBaseHost);
        $baseSearchUriConfigurationDto->setKeywordsPlacement($keywordsPlacement);
        $baseSearchUriConfigurationDto->setStructure($structure);
        $baseSearchUriConfigurationDto->setEncodeQuery($encodeQuery);
        $baseSearchUriConfigurationDto->setResolver($resolver);

        return $baseSearchUriConfigurationDto;
    }

    /**
     * Will build and return {@see BaseDetailPageConfigurationDto}
     *
     * @param array $parsedFile
     * @return BaseDetailPageConfigurationDto
     */
    protected function buildBaseDetailPageConfigurationDto(array $parsedFile): BaseDetailPageConfigurationDto
    {
        $baseDetailPageConfigurationDto = new BaseDetailPageConfigurationDto();

        $baseHost = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_DETAIL_PAGE_BASE_HOST) ?? null;
        $baseUri  = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, self::KEY_DETAIL_PAGE_BASE_URI) ?? null;

        $baseDetailPageConfigurationDto->setBaseUri($baseUri);
        $baseDetailPageConfigurationDto->setBaseHost($baseHost);

        return $baseDetailPageConfigurationDto;
    }

    /**
     * Checks crawling delay. If it's below the minimal value then throws exception, otherwise nothing happens
     *
     * @param float $delay
     * @param array $parsedContent
     */
    protected function validateCrawlingDelay(float $delay, array $parsedContent): void
    {
        if ($delay < self::MINIMAL_CRAWL_DELAY) {
            $msg = "Crawl delay is to low! Got {$delay}, minimal expected: "
                   . self::MINIMAL_CRAWL_DELAY
                   . ", parsed content: "
                   . json_encode($parsedContent, JSON_PRETTY_PRINT);
            throw new LogicException($msg);
        }
    }

    /**
     * Handles setting the search uri location (and distance) based `base` configuration from the parsed yaml file
     *
     * Directly modifies the properties of the provided {@see BaseSearchUriConfigurationDto}
     *
     * @param array                         $parsedFile
     * @param BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto
     */
    private function buildSearchUriLocationConfiguration(array $parsedFile, BaseSearchUriConfigurationDto $baseSearchUriConfigurationDto): void
    {
        $locationPlacement                = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_PLACEMENT) ?? null;
        $locationFormatterFunction        = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_FORMATTER_FUNCTION) ?? null;
        $locationQueryParamName           = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_PLACEMENT_QUERY_PARAM_NAME) ?? null;
        $locationUriPartPrefix            = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_PLACEMENT_URI_PART_PREFIX) ?? "";
        $locationUriPartHasTrailingSlash  = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_PLACEMENT_URI_PART_HAS_TRAILING_SLASH) ?? false;
        $locationSpacebarReplaceCharacter = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_SPACEBAR_REPLACE_CHARACTER) ?? null;

        $locationDistancePlacement               = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_DISTANCE_PLACEMENT) ?? null;
        $locationDistanceQueryParamName          = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_DISTANCE_QUERY_PARAM_NAME) ?? null;
        $locationDistanceUriPartHasTrailingSlash = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_DISTANCE_URI_PART_HAS_TRAILING_SLASH) ?? false;
        $locationDistanceAllowedDistances        = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_DISTANCE_ALLOWED_DISTANCES) ?? [];
        $locationDistanceDefaultRequired         = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFile, LocationSearchUriConstants::KEY_CONFIGURATION_LOCATION_DISTANCE_DEFAULT_REQUIRED) ?? null;

        $locationNameConfiguration = new BaseLocationName(
            $locationPlacement,
            $locationQueryParamName,
            $locationUriPartPrefix,
            $locationUriPartHasTrailingSlash,
            $locationFormatterFunction,
            $locationSpacebarReplaceCharacter
        );

        $locationDistanceConfiguration = new BaseLocationDistance(
            $locationDistancePlacement,
            $locationDistanceQueryParamName,
            $locationDistanceUriPartHasTrailingSlash,
            $locationDistanceAllowedDistances,
            $locationDistanceDefaultRequired
        );

        $baseSearchUriConfigurationDto->setLocationDistanceConfiguration($locationDistanceConfiguration);
        $baseSearchUriConfigurationDto->setLocationNameConfiguration($locationNameConfiguration);
    }

    /**
     * Will validate the base configuration file structure
     *
     * @throws Exception
     */
    private function validateBaseConfigurationStructure(array $parsedFile): void
    {
        # Main
        ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_HOST);

        # Configuration
        ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_CONFIGURATION_NAME);
        ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_CONFIGURATION_ENABLED);

        # Search Uri
        $this->searchUriConfigurationBuilder->validateBaseSearchUriConfiguration($parsedFile);

        ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_SEARCH_URI_BASE_HOST);

        # Pagination
        ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_PAGINATION_MULTIPLE_KEYWORDS_SEPARATOR_CHARACTER);
        ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFile, self::KEY_PAGINATION_PAGE_NUMBER_QUERY_PARAMETER_NAME);
    }

    /**
     * The job services sometimes have pretty much same configurations for multiple countries,
     * so this allows loading the common yaml part, such imported data is then merged into original results
     *
     * Keep in mind that the import data will OVERWRITE any duplicates in main data
     *
     * @param array $parsingResult
     *
     * @return array - results array
     */
    private function handleSharedConfig(mixed $parsingResult): array
    {
        $sharedConfigDirPath   = $this->kernel->getProjectDir() . "/config/packages/jobServices/shared-config/";
        $importedShareFileName = $parsingResult['imported_share_file_name'] ?? null;
        if (empty($importedShareFileName)) {
            return $parsingResult;
        }

        $sharedImportData = Yaml::parseFile($sharedConfigDirPath . $importedShareFileName, Yaml::PARSE_CONSTANT);

        return array_merge_recursive($parsingResult, $sharedImportData);
    }

}