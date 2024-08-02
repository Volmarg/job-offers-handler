<?php

namespace JobSearcher\Service\Normalizer\Location;

/**
 * Handles normalizing city name
 */
class CityNameNormalizer
{
    /**
     * @param string $cityName
     *
     * @return string
     */
    public static function normalize(string $cityName): string
    {
        // remove numbers
        $cityName = preg_replace("#[\d]*#", "", $cityName);

        // remove multiple spacebars
        $cityName = preg_replace("# {2,}#", " ", $cityName);

        // remove leading / trailing empty characters
        $cityName = trim($cityName);

        return $cityName;
    }

}