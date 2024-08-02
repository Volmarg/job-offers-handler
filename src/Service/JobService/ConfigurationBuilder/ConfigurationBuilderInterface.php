<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder;

/**
 * Stores some base logic related to the configuration builders
 */
interface ConfigurationBuilderInterface
{
    const KEYWORDS_URI_PLACEHOLDER        = "{KEYWORDS}";
    const KEYWORDS_PLACEMENT_QUERY        = "KEYWORDS_PLACEMENT_QUERY";
    const KEYWORDS_PLACEMENT_REQUEST_BODY = "KEYWORDS_PLACEMENT_REQUEST_BODY";
}