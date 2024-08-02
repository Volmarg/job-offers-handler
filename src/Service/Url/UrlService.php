<?php

namespace JobSearcher\Service\Url;

/**
 * Generic logic for url handling
 */
class UrlService
{

    /**
     * Will check if the provided link is absolute
     *
     * @param string $link
     *
     * @return bool
     */
    public static function isAbsoluteUri(string $link): bool
    {
        /** @link https://stackoverflow.com/questions/10687099/how-to-test-if-a-url-string-is-absolute-or-relative */
        $pattern = "/^https?:\/\//i";

        return preg_match($pattern, $link);
    }

    /**
     * Will remove the query string from the url and return the filtered url (without query string)
     *
     * @param string $link
     *
     * @return string
     */
    public static function stripQueryString(string $link): string
    {
        $queryLessUrl = preg_replace("#\?.*#", "", $link);
        return $queryLessUrl;
    }

    /**
     * Check if given url has any query string
     *
     * @param string $url
     *
     * @return bool
     */
    public static function hasQueryString(string $url): bool
    {
        return preg_match("#\?.*#", $url);
    }

    /**
     * Decide if `&` or `?` should be used for appending the parameter
     *
     * @param string $url
     *
     * @return string
     */
    public static function getQueryParamAppendCharacter(string $url): string
    {
        return (self::hasQueryString($url) ? "&" : "?");
    }

    /**
     * Will check if uri has leading slash
     *
     * @param string $uri
     * @return bool
     */
    public static function hasUriLeadingSlash(string $uri): bool
    {
        return str_starts_with($uri, "/");
    }

    /**
     * Will check if uri has leading slash
     *
     * @param string $uri
     * @return bool
     */
    public static function hasUriTrailingSlash(string $uri): bool
    {
        return str_ends_with($uri, "/");
    }

    /**
     * Will append trailing slash if there is none yet
     *
     * @param string $uri
     * @return string
     */
    public static function appendTrailingSlash(string $uri): string
    {
        if (self::hasUriTrailingSlash($uri)) {
            return $uri;
        }

        return $uri . "/";
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public static function getDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return str_replace("www.", "", $host);
    }
}