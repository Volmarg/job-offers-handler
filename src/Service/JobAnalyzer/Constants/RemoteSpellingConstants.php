<?php

namespace JobSearcher\Service\JobAnalyzer\Constants;

use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;

class RemoteSpellingConstants
{
    public const ENG_VARIANTS = [
        "remote",
        "home office"
    ];

    public const POL_VARIANTS = [
        "zdalnie",
        "zdalna",
        ...self::ENG_VARIANTS
    ];

    public const FRA_VARIANTS = [
        "Télétravail",
        ...self::ENG_VARIANTS
    ];

    public const ESP_VARIANTS = [
        "remoto",
        ...self::ENG_VARIANTS,
    ];

    /**
     * @param string|null $iso3DigitCode
     *
     * @return array|string[]
     */
    public static function getSpellingVariant(?string $iso3DigitCode): array
    {
        return match (strtoupper($iso3DigitCode)) {
            Iso3166CountryCodeEnum::SPAIN_3_DIGIT->value  => RemoteSpellingConstants::ESP_VARIANTS,
            Iso3166CountryCodeEnum::FRANCE_3_DIGIT->value => RemoteSpellingConstants::FRA_VARIANTS,
            Iso3166CountryCodeEnum::POLAND_3_DIGIT->value => RemoteSpellingConstants::POL_VARIANTS,
            default                                       => RemoteSpellingConstants::ENG_VARIANTS,
        };
    }
}