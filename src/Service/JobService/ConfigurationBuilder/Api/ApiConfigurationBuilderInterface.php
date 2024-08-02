<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder\Api;

/**
 * Defines common logic or consist of data to prevent from bloating
 */
interface ApiConfigurationBuilderInterface
{
    public const SCRAP_ENGINE_GUZZLE = "GUZZLE";
    public const SCRAP_ENGINE_CLI_CURL = "CLI_CURL";

    /**
     * Json configuration accepts this as "OR", for example this configuration is valid:
     * url: "url|link"
     *
     * This means that the value for "url" can be found under key "url", or "link".
     */
    public const JSON_OR_SEPARATOR = "|";

    /**
     * Means that the identifier / slug / uri (or whatever the page is using to recognize the job offer id)
     * is added into the request body (not in uri rather as request parameter)
     */
    const DETAIL_PAGE_IDENTIFIER_PLACEMENT_RAW_BODY = "DETAIL_PAGE_IDENTIFIER_PLACEMENT_RAW_BODY";

    /**
     * Means that the identifier / slug / uri (or whatever the page is using to recognize the job offer id)
     * is added directly to the url
     */
    const DETAIL_PAGE_IDENTIFIER_PLACEMENT_URI = "DETAIL_PAGE_IDENTIFIER_PLACEMENT_URI";

    const KEY_CRAWL_DELAY = "crawl_delay";

    /**
     * The string used to insert in between host and uri
     */
    const KEY_HOST_GLUE_STRING = "detail_page.host_uri_glue_string";

    const KEY_SEARCH_URI_METHOD          = "search_uri.method";
    const KEY_SEARCH_URI_RAW_BODY_PARAMS = "search_uri.request.rawBody.params";
    const KEY_SEARCH_URI_HEADERS         = "search_uri.request.headers";
    const KEY_SEARCH_URI_SCRAP_ENGINE    = "search_uri.scrap_engine";

    /**
     * This means that the added identifier will be prefixed with slash
     */
    public const KEY_DETAIL_PAGE_IDENTIFIER_IS_AFTER_SLASH = "detail_page.identifier_is_after_slash";
    const KEY_DETAIL_PAGE_IDENTIFIER_PLACEMENT                   = "detail_page.identifier_placement";
    const KEY_DETAIL_PAGE_RAW_BODY_PARAMS                        = "detail_page.request.rawBody.params";
    const KEY_DETAIL_PAGE_HEADERS                                = "detail_page.request.headers";
    const KEY_DETAIL_PAGE_METHOD                                 = "detail_page.method";
    const KEY_DETAIL_PAGE_DATA_RESOLVER                          = "detail_page.data_resolver";
    const KEY_DETAIL_PAGE_DESCRIPTION_REMOVED_ELEMENTS_SELECTORS = "detail_page.description.removed_elements_selectors";

    const KEY_NAME     = "name";
    const KEY_VALUE    = "value";
    const KEY_CHILDREN = "children";

    const KEY_ALL_JOBS_DATA                                  = "json_structure.all_jobs";
    const KEY_JSON_STRUCTURE_COMPANY_NAME                    = "json_structure.job_detail.company_name";
    const KEY_JSON_STRUCTURE_JOB_POSTED_DATE_TIME            = "json_structure.job_detail.job_posted_date_time";
    const KEY_JSON_STRUCTURE_DETAIL_IDENTIFIER_FIELD         = "json_structure.job_detail.detail_page_identifier_field";
    const KEY_JSON_STRUCTURE_JOB_DETAIL_MORE_INFORMATION     = "json_structure.job_detail.more_information";

    const KEY_JSON_STRUCTURE_JOB_TITLE       = "json_structure.job_detail.title";
    const KEY_JSON_STRUCTURE_JOB_DESCRIPTION = "json_structure.job_detail.description";
    const KEY_JSON_STRUCTURE_JOB_URL         = "json_structure.job_detail.url";

    const KEY_JSON_STRUCTURE_JOB_LOCATION_TYPE = "json_structure.job_detail.location.type";

    // represents single entry representing location as a whole (can be used when location data is not separated between fields)
    const KEY_JSON_STRUCTURE_JOB_LOCATION_SINGLE_ENTRY_PATH    = "json_structure.job_detail.location.single_entry_path";
    const KEY_JSON_STRUCTURE_JOB_LOCATION_ARRAY_STRUCTURE_PATH = "json_structure.job_detail.location.array_structure_path";

}