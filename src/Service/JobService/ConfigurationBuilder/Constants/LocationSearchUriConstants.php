<?php

namespace JobSearcher\Service\JobService\ConfigurationBuilder\Constants;

/**
 * Constants related to the location based uri search:
 * - {@see LocationUrlHandlerService}
 */
class LocationSearchUriConstants
{
    public const LOCATION_PLACEHOLDER        = "{LOCATION}";
    public const LOCATION_PREFIX_PLACEHOLDER = "{LOCATION_PREFIX}";
    public const DISTANCE_PLACEHOLDER        = "{DISTANCE}";

    public const LOCATION_PLACEMENT_QUERY    = "QUERY";
    public const LOCATION_PLACEMENT_URI_PART = "URI_PART";

    public const KEY_CONFIGURATION_LOCATION_PLACEMENT                             = "search_uri.location.placement";
    public const KEY_CONFIGURATION_LOCATION_SPACEBAR_REPLACE_CHARACTER            = "search_uri.location.spacebar_replace_character";
    public const KEY_CONFIGURATION_LOCATION_FORMATTER_FUNCTION                    = "search_uri.location.formatter_function";
    public const KEY_CONFIGURATION_LOCATION_PLACEMENT_QUERY_PARAM_NAME            = "search_uri.location.query.param_name";
    public const KEY_CONFIGURATION_LOCATION_PLACEMENT_URI_PART_PREFIX             = "search_uri.location.uri_part.prefix";
    public const KEY_CONFIGURATION_LOCATION_PLACEMENT_URI_PART_HAS_TRAILING_SLASH = "search_uri.location.uri_part.has_trailing_slash";

    public const KEY_CONFIGURATION_LOCATION_DISTANCE_PLACEMENT                   = "search_uri.location.distance.placement";
    public const KEY_CONFIGURATION_LOCATION_DISTANCE_QUERY_PARAM_NAME            = "search_uri.location.distance.query.param_name";
    public const KEY_CONFIGURATION_LOCATION_DISTANCE_URI_PART_HAS_TRAILING_SLASH = "search_uri.location.distance.uri_part.has_trailing_slash";
    public const KEY_CONFIGURATION_LOCATION_DISTANCE_ALLOWED_DISTANCES           = "search_uri.location.distance.allowed_distances";
    public const KEY_CONFIGURATION_LOCATION_DISTANCE_DEFAULT_REQUIRED            = "search_uri.location.distance.default_required";
}