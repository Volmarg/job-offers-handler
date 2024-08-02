<?php

namespace JobSearcher\Service\JobAnalyzer;

/**
 * Defines common logic or consist of data to prevent from bloating
 */
interface JobSearchResultAnalyzerInterface
{
    const KEYWORDS_TYPE_INCLUDED_MANDATORY = "includedMandatory";
    const KEYWORDS_TYPE_INCLUDED           = "included";
    const KEYWORDS_TYPE_EXCLUDED           = "excluded";
    const KEY_TOTAL_COUNT                  = "totalCount";

    /**
     * @see JobSearchResultAnalyzerService::calculatePercentageOfExcludedKeywordsOverAllTotallyFound()
     */
    const MINIMUM_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES  = 40;
    const DEMANDED_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES = 80;

}