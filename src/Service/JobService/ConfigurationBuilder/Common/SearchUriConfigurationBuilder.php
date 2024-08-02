<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder\Common;

use Exception;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriBase\BaseSearchUriDto;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;

/**
 * Handles building configuration in context of the search uri logic
 */
class SearchUriConfigurationBuilder
{
    const KEY_SEARCH_URI_BASE_STANDARD            = "search_uri.base_uri.standard";             # mandatory
    const KEY_SEARCH_URI_BASE_SORTED_LATEST_FIRST = "search_uri.base_uri.sorted_latest_first";  # not mandatory

    /**
     * Returns dto that contains variety of base search uris that can be used to obtain different search results
     *
     * @param array $parsedFileContent
     * @return BaseSearchUriDto
     */
    public function buildBaseSearchUri(array $parsedFileContent): BaseSearchUriDto
    {
        $searchUriBaseStandard          = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFileContent, self::KEY_SEARCH_URI_BASE_STANDARD);
        $searchUriBaseSortedLatestFirst = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsedFileContent, self::KEY_SEARCH_URI_BASE_SORTED_LATEST_FIRST);

        $baseSearchUri = new BaseSearchUriDto($searchUriBaseStandard, $searchUriBaseSortedLatestFirst);
        return $baseSearchUri;
    }

    /**
     * Will check if the configuration structure is valid in terms of search uri
     *
     * @throws Exception
     */
    public function validateBaseSearchUriConfiguration(array $parsedFileContent): void
    {
        # base uri
        ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($parsedFileContent, self::KEY_SEARCH_URI_BASE_STANDARD);
    }
}