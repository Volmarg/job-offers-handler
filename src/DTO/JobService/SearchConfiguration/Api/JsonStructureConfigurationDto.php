<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Api;

/**
 * Holds the structure pointing which json node contains given information
 */
class JsonStructureConfigurationDto
{
    const LOCATION_TYPE_SINGLE_PATH = "LOCATION_TYPE_SINGLE_PATH";
    const LOCATION_TYPE_ARRAY       = "LOCATION_TYPE_ARRAY";

    /**
     * @var string|null $jobOfferUrl
     */
    private ?string $jobOfferUrl = null;

    /**
     * @var string|null $locationType
     */
    private ?string $locationType;

    /**
     * If this is a {@see JsonStructureConfigurationDto::LOCATION_TYPE_SINGLE_PATH} then this path will just extract location
     * from the extracted jobOffer information
     *
     * However, if this is {@see JsonStructureConfigurationDto::LOCATION_TYPE_ARRAY}, then this path is a path
     * INSIDE the array extracted by using {@see JsonStructureConfigurationDto::getLocationArrayStructurePath()}
     *
     * @var string|null $locationSingleEntryPath
     */
    private ?string $locationSingleEntryPath;

    /**
     * @var string|null $locationArrayStructurePath
     */
    private ?string $locationArrayStructurePath;

    /**
     * @var string|null $jobDetailMoreInformation
     */
    private ?string $jobDetailMoreInformation;

    /**
     * @var string $jobTitle
     */
    private string $jobTitle;

    /**
     * @var string $jobDescription
     */
    private string $jobDescription;

    /**
     * @var string $companyName
     */
    private string $companyName;

    /**
     * @var string|null $jobPostedDateTime
     */
    private ?string $jobPostedDateTime;

    /**
     * @var string $detailPageIdentifierField
     */
    private string $detailPageIdentifierField;

    /**
     * @var string $allJobsData
     */
    private string $allJobsData;

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string
     */
    public function getDetailPageIdentifierField(): string
    {
        return $this->detailPageIdentifierField;
    }

    /**
     * @param string $detailPageIdentifierField
     */
    public function setDetailPageIdentifierField(string $detailPageIdentifierField): void
    {
        $this->detailPageIdentifierField = $detailPageIdentifierField;
    }

    /**
     * @return string
     */
    public function getAllJobsData(): string
    {
        return $this->allJobsData;
    }

    /**
     * @param string $allJobsData
     */
    public function setAllJobsData(string $allJobsData): void
    {
        $this->allJobsData = $allJobsData;
    }

    /**
     * @return string
     */
    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    /**
     * @param string $jobTitle
     */
    public function setJobTitle(string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    /**
     * @return string
     */
    public function getJobDescription(): string
    {
        return $this->jobDescription;
    }

    /**
     * @param string $jobDescription
     */
    public function setJobDescription(string $jobDescription): void
    {
        $this->jobDescription = $jobDescription;
    }

    /**
     * @return string|null
     */
    public function getJobDetailMoreInformation(): ?string
    {
        return $this->jobDetailMoreInformation;
    }

    /**
     * @param string|null $jobDetailMoreInformation
     */
    public function setJobDetailMoreInformation(?string $jobDetailMoreInformation): void
    {
        $this->jobDetailMoreInformation = $jobDetailMoreInformation;
    }

    /**
     * @return string|null
     */
    public function getLocationType(): ?string
    {
        return $this->locationType;
    }

    /**
     * @param string|null $locationType
     */
    public function setLocationType(?string $locationType): void
    {
        $this->locationType = $locationType;
    }

    /**
     * @return string|null
     */
    public function getJobPostedDateTime(): ?string
    {
        return $this->jobPostedDateTime;
    }

    /**
     * @param string|null $jobPostedDateTime
     */
    public function setJobPostedDateTime(?string $jobPostedDateTime): void
    {
        $this->jobPostedDateTime = $jobPostedDateTime;
    }

    /**
     * @return string|null
     */
    public function getLocationSingleEntryPath(): ?string
    {
        return $this->locationSingleEntryPath;
    }

    /**
     * @param string|null $locationSingleEntryPath
     */
    public function setLocationSingleEntryPath(?string $locationSingleEntryPath): void
    {
        $this->locationSingleEntryPath = $locationSingleEntryPath;
    }

    /**
     * @return string|null
     */
    public function getLocationArrayStructurePath(): ?string
    {
        return $this->locationArrayStructurePath;
    }

    /**
     * @param string|null $locationArrayStructurePath
     */
    public function setLocationArrayStructurePath(?string $locationArrayStructurePath): void
    {
        $this->locationArrayStructurePath = $locationArrayStructurePath;
    }

    /**
     * @return bool
     */
    public function isLocationTypeSinglePath(): bool
    {
        return ($this->locationType === self::LOCATION_TYPE_SINGLE_PATH);
    }

    /**
     * @return bool
     */
    public function isLocationTypeArray(): bool
    {
        return ($this->locationType === self::LOCATION_TYPE_ARRAY);
    }

    /**
     * @return string|null
     */
    public function getJobOfferUrl(): ?string
    {
        return $this->jobOfferUrl;
    }

    /**
     * @param string|null $jobOfferUrl
     */
    public function setJobOfferUrl(?string $jobOfferUrl): void
    {
        $this->jobOfferUrl = $jobOfferUrl;
    }

}