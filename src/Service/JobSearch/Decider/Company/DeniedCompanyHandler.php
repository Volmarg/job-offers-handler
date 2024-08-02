<?php

namespace JobSearcher\Service\JobSearch\Decider\Company;

use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;

class DeniedCompanyHandler
{
    public const DENIED_COMPANY_NAME_FOR_ESP = [
        "confidencial",
    ];
    public const DENIED_COMPANY_NAME_FOR_POL = [
        "Agencja pracy GoWork.pl",
    ];

    /**
     * Checks if company name is denied for the country code
     *
     * @param string $country3DigitIsoCode
     * @param string $companyName
     *
     * @return bool
     */
    public static function isNameDenied(string $country3DigitIsoCode, string $companyName): bool
    {
        $deniedStrings = match (strtoupper($country3DigitIsoCode)) {
            Iso3166CountryCodeEnum::SPAIN_3_DIGIT->value  => self::DENIED_COMPANY_NAME_FOR_ESP,
            Iso3166CountryCodeEnum::POLAND_3_DIGIT->value => self::DENIED_COMPANY_NAME_FOR_POL,
            default => [],
        };

        foreach ($deniedStrings as $deniedString) {
            if (str_contains($companyName, $deniedString)) {
                return true;
            }
        }

        return false;
    }
}
