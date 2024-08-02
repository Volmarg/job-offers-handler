<?php

namespace JobSearcher\Service\JobSearch\UrlHandler;

/**
 * Defines common logic or consist of data to prevent from bloating
 */
interface AbstractUrlHandlerInterface
{
    const URL_TYPE_DETAIL_PAGE     = "DETAIL_PAGE";
    const URL_TYPE_PAGINATION_PAGE = "PAGINATION_PAGE";
}