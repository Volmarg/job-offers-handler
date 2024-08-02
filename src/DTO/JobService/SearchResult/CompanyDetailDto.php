<?php

namespace JobSearcher\DTO\JobService\SearchResult;

/**
 * Contain company details
 */
class CompanyDetailDto
{
    private const KEY_COMPANY_NAME         = 'companyName';
    private const KEY_COMPANY_LOCATIONS    = 'companyLocations';
    private const KEY_WEBSITE_URL          = 'websiteUrl';
    private const KEY_LINKEDIN_PROFILE_URL = 'linkedinProfileUrl';
    private const KEY_EMPLOYEES_COUNT_HIGH = 'employeesCountHigh';
    private const KEY_AGE_OLD              = 'ageOld';
    private const KEY_INDUSTRIES           = 'industries';

    /**
     * @var array $industries
     */
    private array $industries = [];

    /**
     * @var string|null $companyName
     */
    private ?string $companyName = "";

    /**
     * @var array $companyLocations
     */
    private array $companyLocations = [];

    /**
     * @var string|null $websiteUrl
     */
    private ?string $websiteUrl = null;

    /**
     * @var string|null $linkedinProfileUrl
     */
    private ?string $linkedinProfileUrl = null;

    /**
     * @var string|null $employeesRange
     */
    private ?string $employeesRange = null;

    /**
     * @var int|null $ageOld
     */
    private ?int $ageOld = null;

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string|null $companyName
     */
    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return array
     */
    public function getCompanyLocations(): array
    {
        return $this->companyLocations;
    }

    /**
     * @param array $companyLocations
     */
    public function setCompanyLocations(array $companyLocations): void
    {
        $this->companyLocations = $companyLocations;
    }

    /**
     * @param string $locationName
     */
    public function addCompanyLocation(string $locationName): void
    {
        $this->companyLocations[] = $locationName;
    }

    /**
     * @return array
     */
    public function getIndustries(): array
    {
        return $this->industries;
    }

    /**
     * @param array $industries
     */
    public function setIndustries(array $industries): void
    {
        $this->industries = $industries;
    }

    /**
     * Return array representation of the dto
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::KEY_INDUSTRIES           => $this->getIndustries(),
            self::KEY_COMPANY_LOCATIONS    => $this->getCompanyLocations(),
            self::KEY_COMPANY_NAME         => $this->getCompanyName(),
            self::KEY_AGE_OLD              => $this->getAgeOld(),
            self::KEY_EMPLOYEES_COUNT_HIGH => $this->getEmployeesRange(),
            self::KEY_LINKEDIN_PROFILE_URL => $this->getLinkedinProfileUrl(),
            self::KEY_WEBSITE_URL          => $this->getWebsiteUrl(),
        ];
    }

    /**
     * @return string|null
     */
    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    /**
     * @param string|null $websiteUrl
     */
    public function setWebsiteUrl(?string $websiteUrl): void
    {
        $this->websiteUrl = $websiteUrl;
    }

    /**
     * @return string|null
     */
    public function getLinkedinProfileUrl(): ?string
    {
        return $this->linkedinProfileUrl;
    }

    /**
     * @param string|null $linkedinProfileUrl
     */
    public function setLinkedinProfileUrl(?string $linkedinProfileUrl): void
    {
        $this->linkedinProfileUrl = $linkedinProfileUrl;
    }

    /**
     * @return int|null
     */
    public function getAgeOld(): ?int
    {
        return $this->ageOld;
    }

    /**
     * @param int|null $ageOld
     */
    public function setAgeOld(?int $ageOld): void
    {
        $this->ageOld = $ageOld;
    }

    /**
     * @return string|null
     */
    public function getEmployeesRange(): ?string
    {
        return $this->employeesRange;
    }

    /**
     * @param string|null $employeesRange
     */
    public function setEmployeesRange(?string $employeesRange): void
    {
        $this->employeesRange = $employeesRange;
    }

}