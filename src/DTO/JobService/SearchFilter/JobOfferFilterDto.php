<?php
namespace JobSearcher\DTO\JobService\SearchFilter;

use JobSearcher\DTO\JobService\SearchFilter\SubFilters\JobPostedDateTimeFilterDto;
use JobSearcher\Service\JobAnalyzer\JobSearchResultAnalyzerService;
use Exception;

/**
 * Contain all the data with which the data get filtered / analyzed later on
 */
class JobOfferFilterDto
{
    /**
     * Indicates that All the keywords must be present
     */
    const KEYWORDS_CHECK_MODE_ALL = "ALL";

    /**
     * At least one keyword must be present
     */
    const KEYWORDS_CHECK_MODE_ANY = "ANY";

    /**
     * Will check keywords count is equal to System Defined Percentage of total keywords found
     * -{@see JobSearchResultAnalyzerService::MINIMUM_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES}
     * -{@see JobSearchResultAnalyzerService::DEMANDED_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES}
     *
     */
    const KEYWORDS_CHECK_MODE_PERCENTAGE = "PERCENTAGE";

    /**
     * @var array $locationNames
     */
    private array $locationNames = [];

    /**
     * @var array $countryNames
     */
    private array $countryNames = [];

    /**
     * @var int $maxKmDistanceFromLocationName
     */
    private int $maxKmDistanceFromLocationName = 0;

    /**
     * @var array $includedKeywords
     */
    private array $includedKeywords = [];

    /**
     * @var array $mandatoryIncludedKeywords
     */
    private array $mandatoryIncludedKeywords = [];

    /**
     * @var array $excludedKeywords
     */
    private array $excludedKeywords = [];

    /**
     * @var array $mandatoryHumanLanguages
     */
    private array $mandatoryHumanLanguages = [];

    /**
     * @var bool $includeJobOffersWithoutHumanLanguagesMentioned
     */
    private bool $includeJobOffersWithoutHumanLanguagesMentioned = true;

    /**
     * @var int $minSalary
     */
    private int $minSalary = 0;

    /**
     * @var int $maxSalary
     */
    private int $maxSalary = 0;

    /**
     * @var bool $mustHaveMail
     */
    private bool $mustHaveMail = false;

    /**
     * @var bool $mustHavePhone
     */
    private bool $mustHavePhone = false;

    /**
     * @var bool $mustHaveSalary
     */
    private bool $mustHaveSalary = false;

    /**
     * @var bool $mustHaveLocation
     */
    private bool $mustHaveLocation = false;

    /**
     * @var bool $mustBeRemote
     */
    private bool $mustBeRemote = false;

    /**
     * @var bool $includeJobOfferDetailWithoutLanguageDetected
     */
    private bool $includeJobOfferDetailWithoutLanguageDetected = true;

    /**
     * @var string $includedKeywordsCheckMode
     */
    private string $includedKeywordsCheckMode = self::KEYWORDS_CHECK_MODE_ANY;

    /**
     * @var string $excludedKeywordsCheckMode
     */
    private string $excludedKeywordsCheckMode = self::KEYWORDS_CHECK_MODE_ANY;

    /**
     * @var string $mandatoryIncludedKeywordsCheckMode
     */
    private string $mandatoryIncludedKeywordsCheckMode = self::KEYWORDS_CHECK_MODE_ALL;

    /**
     * @var JobPostedDateTimeFilterDto $jobPostedDateTimeFilterDto
     */
    private JobPostedDateTimeFilterDto $jobPostedDateTimeFilterDto;

    /**
     * @var bool $includeJobOfferWithoutPostedDateTime
     */
    private bool $includeJobOfferWithoutPostedDateTime = false;

    /**
     * @var bool $treatOfferDescriptionLanguageAsHumanLanguage
     */
    private bool $treatOfferDescriptionLanguageAsHumanLanguage = true;

    /**
     * @var int|null $companyMinYearsOld
     */
    private ?int $companyMinYearsOld = null;

    /**
     * @var bool $includeOffersWithoutCompanyFoundedYear
     */
    private bool $includeOffersWithoutCompanyFoundedYear = true;

    /**
     * @var int|null $companyEmployeesMinCount
     */
    private ?int $companyEmployeesMinCount = null;

    /**
     * @var bool $includeOffersWithoutEmployeesCount
     */
    private bool $includeOffersWithoutEmployeesCount = true;

    /**
     * @var bool $onlyNewOffers
     */
    private bool $onlyNewOffers = true;

    /**
     * @var array $excludedCompanyNames
     */
    private array $excludedCompanyNames = [];

    /**
     * @var array $includedCompanyNames
     */
    private array $includedCompanyNames = [];

    /**
     * @var bool $countryNameRequired
     */
    private bool $countryNameRequired = false;

    /**
     * @var bool $employeeCountRequired
     */
    private bool $employeeCountRequired = false;

    /**
     * @var bool $ageRequired
     */
    private bool $ageRequired = false;

    /**
     * Key is the color, value are the keywords
     *
     * @var array
     */
    private array $highlightedKeywords = [];

    public function __construct()
    {
        $this->jobPostedDateTimeFilterDto = new JobPostedDateTimeFilterDto();
    }

    /**
     * @return array
     */
    public function getIncludedKeywords(): array
    {
        return $this->includedKeywords;
    }

    /**
     * @param array $includedKeywords
     */
    public function setIncludedKeywords(array $includedKeywords): void
    {
        $this->includedKeywords = $includedKeywords;
    }

    /**
     * @return array
     */
    public function getExcludedKeywords(): array
    {
        return $this->excludedKeywords;
    }

    /**
     * @param array $excludedKeywords
     */
    public function setExcludedKeywords(array $excludedKeywords): void
    {
        $this->excludedKeywords = $excludedKeywords;
    }

    /**
     * @return int
     */
    public function getMinSalary(): int
    {
        return $this->minSalary;
    }

    /**
     * @param int $minSalary
     */
    public function setMinSalary(int $minSalary): void
    {
        $this->minSalary = $minSalary;
    }

    /**
     * @return int
     */
    public function getMaxSalary(): int
    {
        return $this->maxSalary;
    }

    /**
     * @param int $maxSalary
     */
    public function setMaxSalary(int $maxSalary): void
    {
        $this->maxSalary = $maxSalary;
    }

    /**
     * @return bool
     */
    public function isMustHaveMail(): bool
    {
        return $this->mustHaveMail;
    }

    /**
     * @param bool $mustHaveMail
     */
    public function setMustHaveMail(bool $mustHaveMail): void
    {
        $this->mustHaveMail = $mustHaveMail;
    }

    /**
     * @return bool
     */
    public function isMustHavePhone(): bool
    {
        return $this->mustHavePhone;
    }

    /**
     * @param bool $mustHavePhone
     */
    public function setMustHavePhone(bool $mustHavePhone): void
    {
        $this->mustHavePhone = $mustHavePhone;
    }

    /**
     * @return bool
     */
    public function isMustHaveSalary(): bool
    {
        return $this->mustHaveSalary;
    }

    /**
     * @param bool $mustHaveSalary
     */
    public function setMustHaveSalary(bool $mustHaveSalary): void
    {
        $this->mustHaveSalary = $mustHaveSalary;
    }

    /**
     * @return bool
     */
    public function isMustHaveLocation(): bool
    {
        return $this->mustHaveLocation;
    }

    /**
     * @param bool $mustHaveLocation
     */
    public function setMustHaveLocation(bool $mustHaveLocation): void
    {
        $this->mustHaveLocation = $mustHaveLocation;
    }

    /**
     * @return array
     */
    public function getMandatoryIncludedKeywords(): array
    {
        return $this->mandatoryIncludedKeywords;
    }

    /**
     * @param array $mandatoryIncludedKeywords
     */
    public function setMandatoryIncludedKeywords(array $mandatoryIncludedKeywords): void
    {
        $this->mandatoryIncludedKeywords = $mandatoryIncludedKeywords;
    }

    /**
     * @return string
     */
    public function getIncludedKeywordsCheckMode(): string
    {
        return $this->includedKeywordsCheckMode;
    }

    /**
     * @param string $includedKeywordsCheckMode
     */
    public function setIncludedKeywordsCheckMode(string $includedKeywordsCheckMode): void
    {
        $this->includedKeywordsCheckMode = $includedKeywordsCheckMode;
    }

    /**
     * @return string
     */
    public function getExcludedKeywordsCheckMode(): string
    {
        return $this->excludedKeywordsCheckMode;
    }

    /**
     * @param string $excludedKeywordsCheckMode
     */
    public function setExcludedKeywordsCheckMode(string $excludedKeywordsCheckMode): void
    {
        $this->excludedKeywordsCheckMode = $excludedKeywordsCheckMode;
    }

    /**
     * @return string
     */
    public function getMandatoryIncludedKeywordsCheckMode(): string
    {
        return $this->mandatoryIncludedKeywordsCheckMode;
    }

    /**
     * @param string $mandatoryIncludedKeywordsCheckMode
     */
    public function setMandatoryIncludedKeywordsCheckMode(string $mandatoryIncludedKeywordsCheckMode): void
    {
        $this->mandatoryIncludedKeywordsCheckMode = $mandatoryIncludedKeywordsCheckMode;
    }

    /**
     * @return bool
     */
    public function isMustBeRemote(): bool
    {
        return $this->mustBeRemote;
    }

    /**
     * @param bool $mustBeRemote
     */
    public function setMustBeRemote(bool $mustBeRemote): void
    {
        $this->mustBeRemote = $mustBeRemote;
    }

    /**
     * @return bool
     */
    public function isIncludeJobOfferWithoutPostedDateTime(): bool
    {
        return $this->includeJobOfferWithoutPostedDateTime;
    }

    /**
     * @param bool $includeJobOfferWithoutPostedDateTime
     */
    public function setIncludeJobOfferWithoutPostedDateTime(bool $includeJobOfferWithoutPostedDateTime): void
    {
        $this->includeJobOfferWithoutPostedDateTime = $includeJobOfferWithoutPostedDateTime;
    }

    /**
     * @return JobPostedDateTimeFilterDto
     */
    public function getJobPostedDateTimeFilterDto(): JobPostedDateTimeFilterDto
    {
        return $this->jobPostedDateTimeFilterDto;
    }

    /**
     * @param JobPostedDateTimeFilterDto $jobPostedDateTimeFilterDto
     */
    public function setJobPostedDateTimeFilterDto(JobPostedDateTimeFilterDto $jobPostedDateTimeFilterDto): void
    {
        $this->jobPostedDateTimeFilterDto = $jobPostedDateTimeFilterDto;
    }

    /**
     * @return array
     */
    public function getMandatoryHumanLanguages(): array
    {
        return $this->mandatoryHumanLanguages;
    }

    /**
     * @param array $mandatoryHumanLanguages
     */
    public function setMandatoryHumanLanguages(array $mandatoryHumanLanguages): void
    {
        $this->mandatoryHumanLanguages = $mandatoryHumanLanguages;
    }

    /**
     * @return bool
     */
    public function isIncludeJobOffersWithoutHumanLanguagesMentioned(): bool
    {
        return $this->includeJobOffersWithoutHumanLanguagesMentioned;
    }

    /**
     * @param bool $includeJobOffersWithoutHumanLanguagesMentioned
     */
    public function setIncludeJobOffersWithoutHumanLanguagesMentioned(bool $includeJobOffersWithoutHumanLanguagesMentioned): void
    {
        $this->includeJobOffersWithoutHumanLanguagesMentioned = $includeJobOffersWithoutHumanLanguagesMentioned;
    }

    /**
     * Will validate the dto, throws exception if something fails
     *
     * @throws Exception
     */
    public function validateSelf()
    {
        $this->getJobPostedDateTimeFilterDto()->validateSelf();
    }

    /**
     * @return int
     */
    public function getMaxKmDistanceFromLocationName(): int
    {
        return $this->maxKmDistanceFromLocationName;
    }

    /**
     * @param int $maxKmDistanceFromLocationName
     */
    public function setMaxKmDistanceFromLocationName(int $maxKmDistanceFromLocationName): void
    {
        $this->maxKmDistanceFromLocationName = $maxKmDistanceFromLocationName;
    }

    /**
     * @return bool
     */
    public function isTreatOfferDescriptionLanguageAsHumanLanguage(): bool
    {
        return $this->treatOfferDescriptionLanguageAsHumanLanguage;
    }

    /**
     * @param bool $treatOfferDescriptionLanguageAsHumanLanguage
     */
    public function setTreatOfferDescriptionLanguageAsHumanLanguage(bool $treatOfferDescriptionLanguageAsHumanLanguage): void
    {
        $this->treatOfferDescriptionLanguageAsHumanLanguage = $treatOfferDescriptionLanguageAsHumanLanguage;
    }

    /**
     * Check if the offers are filtered by distance from "X" km from "Y" city
     *
     * @return bool
     */
    public function isSearchByCityDistance(): bool
    {
        return (
                !empty($this->getLocationNames())
            &&  !empty($this->getMaxKmDistanceFromLocationName())
        );
    }

    /**
     * @return bool
     */
    public function isIncludeJobOfferDetailWithoutLanguageDetected(): bool
    {
        return $this->includeJobOfferDetailWithoutLanguageDetected;
    }

    /**
     * @param bool $includeJobOfferDetailWithoutLanguageDetected
     */
    public function setIncludeJobOfferDetailWithoutLanguageDetected(bool $includeJobOfferDetailWithoutLanguageDetected
    ): void {
        $this->includeJobOfferDetailWithoutLanguageDetected = $includeJobOfferDetailWithoutLanguageDetected;
    }

    /**
     * @return int|null
     */
    public function getCompanyMinYearsOld(): ?int
    {
        return $this->companyMinYearsOld;
    }

    /**
     * @param int|null $companyMinYearsOld
     */
    public function setCompanyMinYearsOld(?int $companyMinYearsOld): void
    {
        $this->companyMinYearsOld = $companyMinYearsOld;
    }

    /**
     * @return bool
     */
    public function isIncludeOffersWithoutCompanyFoundedYear(): bool
    {
        return $this->includeOffersWithoutCompanyFoundedYear;
    }

    /**
     * @param bool $includeOffersWithoutCompanyFoundedYear
     */
    public function setIncludeOffersWithoutCompanyFoundedYear(bool $includeOffersWithoutCompanyFoundedYear): void
    {
        $this->includeOffersWithoutCompanyFoundedYear = $includeOffersWithoutCompanyFoundedYear;
    }

    /**
     * @return int|null
     */
    public function getCompanyEmployeesMinCount(): ?int
    {
        return $this->companyEmployeesMinCount;
    }

    /**
     * @param int|null $companyEmployeesMinCount
     */
    public function setCompanyEmployeesMinCount(?int $companyEmployeesMinCount): void
    {
        $this->companyEmployeesMinCount = $companyEmployeesMinCount;
    }

    /**
     * @return bool
     */
    public function isIncludeOffersWithoutEmployeesCount(): bool
    {
        return $this->includeOffersWithoutEmployeesCount;
    }

    /**
     * @param bool $includeOffersWithoutEmployeesCount
     */
    public function setIncludeOffersWithoutEmployeesCount(bool $includeOffersWithoutEmployeesCount): void
    {
        $this->includeOffersWithoutEmployeesCount = $includeOffersWithoutEmployeesCount;
    }

    /**
     * @return array
     */
    public function getCountryNames(): array
    {
        return $this->countryNames;
    }

    /**
     * @param array $countryNames
     */
    public function setCountryNames(array $countryNames): void
    {
        $this->countryNames = $countryNames;
    }

    /**
     * @return array
     */
    public function getLocationNames(): array
    {
        return $this->locationNames;
    }

    /**
     * @param array $locationNames
     */
    public function setLocationNames(array $locationNames): void
    {
        $this->locationNames = $locationNames;
    }

    /**
     * @return array
     */
    public function getHighlightedKeywords(): array
    {
        return $this->highlightedKeywords;
    }

    /**
     * @param array $highlightedKeywords
     */
    public function setHighlightedKeywords(array $highlightedKeywords): void
    {
        $this->highlightedKeywords = $highlightedKeywords;
    }

    /**
     * @return bool
     */
    public function isOnlyNewOffers(): bool
    {
        return $this->onlyNewOffers;
    }

    /**
     * @param bool $onlyNewOffers
     */
    public function setOnlyNewOffers(bool $onlyNewOffers): void
    {
        $this->onlyNewOffers = $onlyNewOffers;
    }

    /**
     * @return array
     */
    public function getExcludedCompanyNames(): array
    {
        return $this->excludedCompanyNames;
    }

    /**
     * @return array
     */
    public function getExcludedCompanyNamesLowercase(): array
    {
        return array_map(
            fn(string $name) => mb_strtolower($name),
            $this->excludedCompanyNames,
        );
    }

    /**
     * @return array
     */
    public function getIncludedCompanyNamesLowercase(): array
    {
        return array_map(
            fn(string $name) => mb_strtolower($name),
            $this->includedCompanyNames,
        );
    }

    /**
     * @param array $excludedCompanyNames
     */
    public function setExcludedCompanyNames(array $excludedCompanyNames): void
    {
        $this->excludedCompanyNames = $excludedCompanyNames;
    }

    /**
     * @return array
     */
    public function getIncludedCompanyNames(): array
    {
        return $this->includedCompanyNames;
    }

    /**
     * @param array $includedCompanyNames
     */
    public function setIncludedCompanyNames(array $includedCompanyNames): void
    {
        $this->includedCompanyNames = $includedCompanyNames;
    }

    /**
     * @return bool
     */
    public function isCountryNameRequired(): bool
    {
        return $this->countryNameRequired;
    }

    /**
     * @param bool $countryNameRequired
     */
    public function setCountryNameRequired(bool $countryNameRequired): void
    {
        $this->countryNameRequired = $countryNameRequired;
    }

    /**
     * @return bool
     */
    public function isEmployeeCountRequired(): bool
    {
        return $this->employeeCountRequired;
    }

    /**
     * @param bool $employeeCountRequired
     */
    public function setEmployeeCountRequired(bool $employeeCountRequired): void
    {
        $this->employeeCountRequired = $employeeCountRequired;
    }

    /**
     * @return bool
     */
    public function isAgeRequired(): bool
    {
        return $this->ageRequired;
    }

    /**
     * @param bool $ageRequired
     */
    public function setAgeRequired(bool $ageRequired): void
    {
        $this->ageRequired = $ageRequired;
    }

}