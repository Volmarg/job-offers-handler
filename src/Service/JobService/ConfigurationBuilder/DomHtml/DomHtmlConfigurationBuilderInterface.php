<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder\DomHtml;

/**
 * Defines common logic or consist of data to prevent from bloating
 */
interface DomHtmlConfigurationBuilderInterface
{
    const KEY_SELECTORS = "selectors";

    const KEY_DOM_ELEMENT_PURPOSE            = "dom_element_purpose";
    const KEY_CSS_SELECTOR                   = "css_selector";
    const KEY_CSS_IFRAME_SELECTOR            = "iframe_css_selector";
    const KEY_TARGET_ATTRIBUTE_NAME          = "target_attribute_name";
    const KEY_GET_DATA_FROM_INNER_TEXT       = "get_data_from_inner_text";
    const KEY_DATA_FROM_INNER_TEXT_WITH_HTML = "data_from_inner_text_with_html";
    const KEY_GET_DATA_FROM_ATTRIBUTE        = "get_data_from_attribute";
    const KEY_REMOVED_ELEMENTS_SELECTORS     = "removed_elements_selectors";
    const KEY_CALLED_METHOD_NAME             = "called_method";
    const KEY_CALLED_METHOD_ARGS             = "called_method_args";

    const KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_HEADERS = "crawler_configuration.scrap_pagination.headers";
    const KEY_DOTTED_CRAWLER_CONFIG_DETAILS_HEADERS = "crawler_configuration.scrap_job_offer_detail.headers";

    const KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_ENGINE                             = "crawler_configuration.scrap_pagination.engine";
    const KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_WAIT_MILLISECONDS                  = "crawler_configuration.scrap_pagination.wait_milliseconds";
    const KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_WAIT_FOR_FUNCTION_TO_RETURN_TRUE   = "crawler_configuration.scrap_pagination.wait_for_function_to_return_true";
    const KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_WAIT_FOR_DOM_ELEMENT_SELECTOR_NAME = "crawler_configuration.scrap_pagination.wait_for_dom_element_selector_name";
    const KEY_DOTTED_CRAWLER_CONFIG_PAGINATION_EXTRA_CONFIG                       = "crawler_configuration.scrap_pagination.extra_configuration";

    const KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_ENGINE                             = "crawler_configuration.scrap_job_offer_detail.engine";
    const KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_WAIT_MILLISECONDS                  = "crawler_configuration.scrap_job_offer_detail.wait_milliseconds";
    const KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_WAIT_FOR_FUNCTION_TO_RETURN_TRUE   = "crawler_configuration.scrap_job_offer_detail.wait_for_function_to_return_true";
    const KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_WAIT_FOR_DOM_ELEMENT_SELECTOR_NAME = "crawler_configuration.scrap_job_offer_detail.wait_for_dom_element_selector_name";
    const KEY_DOTTED_CRAWLER_CONFIG_JOB_OFFER_DETAIL_EXTRA_CONFIG                       = "crawler_configuration.scrap_job_offer_detail.extra_configuration";

    const KEY_DOTTED_CRAWLER_CONFIG_CRAWL_DELAY = "crawler_configuration.crawl_delay";

}