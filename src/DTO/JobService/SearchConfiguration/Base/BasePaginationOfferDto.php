<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base;

use DateTime;

/**
 * Base dto that contains information about job offer fetched from the pagination,
 * In some cases, some pages show brief information about offer (in some blocks etc.)
 * For example:
 * - {@link https://www.kimeta.de/search?q=php&r=10} has the block on left
 */
class BasePaginationOfferDto
{
    /**
     * @var string|null $companyName
     */
    private ?string $companyName = null;

    /**
     * @var string|null $jobOfferTitle
     */
    private ?string $jobOfferTitle = null;

    /**
     * @var string|null $jobOfferDescription
     */
    private ?string $jobOfferDescription = null;

    /**
     * @var string|null $companyLocation
     */
    private ?string $companyLocation = null;

    /**
     * @var string $absoluteJobOfferUrl
     */
    private string $absoluteJobOfferUrl;

    /**
     * @var bool $excludedFromScrapping
     */
    private bool $excludedFromScrapping = false;

    /**
     * @var string | null $postedDateTimeString
     */
    private ?string $postedDateTimeString = null;

    /**
     * Can either be relative or absolute - it's not guaranteed which one is it since that link
     * is just being taken from the data on page, for data scrapping use {@see BasePaginationOfferDto::$absoluteJobOfferUrl}
     * @var string
     */
    private string $jobOfferUrl;

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
     * @return string|null
     */
    public function getJobOfferTitle(): ?string
    {
        return $this->jobOfferTitle;
    }

    /**
     * @param string|null $jobOfferTitle
     */
    public function setJobOfferTitle(?string $jobOfferTitle): void
    {
        $this->jobOfferTitle = $jobOfferTitle;
    }

    /**
     * @return string
     */
    public function getAbsoluteJobOfferUrl(): string
    {
        return $this->absoluteJobOfferUrl;
    }

    /**
     * @param string $absoluteJobOfferUrl
     */
    public function setAbsoluteJobOfferUrl(string $absoluteJobOfferUrl): void
    {
        $this->absoluteJobOfferUrl = $absoluteJobOfferUrl;
    }

    /**
     * @return string
     */
    public function getJobOfferUrl(): string
    {
        return $this->jobOfferUrl;
    }

    /**
     * @param string $jobOfferUrl
     */
    public function setJobOfferUrl(string $jobOfferUrl): void
    {
        $this->jobOfferUrl = $jobOfferUrl;
    }

    /**
     * @return bool
     */
    public function isExcludedFromScrapping(): bool
    {
        return $this->excludedFromScrapping;
    }

    /**
     * @param bool $excludedFromScrapping
     */
    public function setExcludedFromScrapping(bool $excludedFromScrapping): void
    {
        $this->excludedFromScrapping = $excludedFromScrapping;
    }

    /**
     * @return string|null
     */
    public function getCompanyLocation(): ?string
    {
        return $this->companyLocation;
    }

    /**
     * @param string|null $companyLocation
     */
    public function setCompanyLocation(?string $companyLocation): void
    {
        $this->companyLocation = $companyLocation;
    }

    /**
     * @return string|null
     */
    public function getPostedDateTimeString(): ?string
    {
        return $this->postedDateTimeString;
    }

    /**
     * @param string|null $postedDateTimeString
     */
    public function setPostedDateTimeString(?string $postedDateTimeString): void
    {
        $this->postedDateTimeString = $postedDateTimeString;
    }

    public function getJobOfferDescription(): ?string
    {
        return $this->jobOfferDescription;
    }

    public function setJobOfferDescription(?string $jobOfferDescription): void
    {
        $this->jobOfferDescription = $jobOfferDescription;
    }

}