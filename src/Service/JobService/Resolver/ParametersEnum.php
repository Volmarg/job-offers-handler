<?php

namespace JobSearcher\Service\JobService\Resolver;

/**
 * All the keys available for parameters used in resolvers
 */
enum ParametersEnum
{
    case MAIN_CONFIGURATION_DTO;
    case BASE_SEARCH_URI_DTO;
    case PAGE_NUMBER;
    case KEYWORDS;
    case MAX_PAGINATION_PAGES;
    case SEARCH_RESULT_DATA;
    case SEARCH_PAGE_REQUEST_BODY_DATA;
    case LOCATION_NAME;
    case SEARCH_URI;
    case HTML_CONTENT;
    case LOCATION_DISTANCE;
    case DETAIL_PAGE_URL;
}