<?php

namespace JobSearcher\DTO\JobService\SearchResultAnalyze;

/**
 * Contain the job offer analyze result - after applying filter rules / checks etc.
 */
class SearchResultAnalyzedDto
{
    /**
     * @var bool $hasJobDateTimePostedInformation
     */
    private bool $hasJobDateTimePostedInformation = false;

    /**
     * @var bool $anyHumanLanguageMentioned
     */
    private bool $anyHumanLanguageMentioned = false;

    /**
     * @var bool $hasMail
     */
    private bool $hasMail = false;

    /**
     * @var bool $hasPhone
     */
    private bool $hasPhone = false;

    /**
     * @var bool $hasSalary
     */
    private bool $hasSalary = false;

    /**
     * @var bool $hasCompanyLocation
     */
    private bool $hasCompanyLocation = false;

    /**
     * @return bool
     */
    public function hasMail(): bool
    {
        return $this->hasMail;
    }

    /**
     * @param bool $hasMail
     */
    public function setHasMail(bool $hasMail): void
    {
        $this->hasMail = $hasMail;
    }

    /**
     * @return bool
     */
    public function hasPhone(): bool
    {
        return $this->hasPhone;
    }

    /**
     * @param bool $hasPhone
     */
    public function setHasPhone(bool $hasPhone): void
    {
        $this->hasPhone = $hasPhone;
    }

    /**
     * @return bool
     */
    public function hasSalary(): bool
    {
        return $this->hasSalary;
    }

    /**
     * @param bool $hasSalary
     */
    public function setHasSalary(bool $hasSalary): void
    {
        $this->hasSalary = $hasSalary;
    }

    /**
     * @return bool
     */
    public function hasCompanyLocation(): bool
    {
        return $this->hasCompanyLocation;
    }

    /**
     * @param bool $hasCompanyLocation
     */
    public function setHasCompanyLocation(bool $hasCompanyLocation): void
    {
        $this->hasCompanyLocation = $hasCompanyLocation;
    }

    /**
     * @return bool
     */
    public function isAnyHumanLanguageMentioned(): bool
    {
        return $this->anyHumanLanguageMentioned;
    }

    /**
     * @param bool $anyHumanLanguageMentioned
     */
    public function setAnyHumanLanguageMentioned(bool $anyHumanLanguageMentioned): void
    {
        $this->anyHumanLanguageMentioned = $anyHumanLanguageMentioned;
    }

    /**
     * @return bool
     */
    public function hasJobDateTimePostedInformation(): bool
    {
        return $this->hasJobDateTimePostedInformation;
    }

    /**
     * @param bool $hasJobDateTimePostedInformation
     */
    public function setHasJobDateTimePostedInformation(bool $hasJobDateTimePostedInformation): void
    {
        $this->hasJobDateTimePostedInformation = $hasJobDateTimePostedInformation;
    }

}