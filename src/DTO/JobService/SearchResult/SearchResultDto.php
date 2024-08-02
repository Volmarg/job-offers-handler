<?php

namespace JobSearcher\DTO\JobService\SearchResult;

use DateTime;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Service\TypeProcessor\RangeTypeProcessor;

/**
 * Job search result dto. Contain all the gathered job offer data from given url
 */
class SearchResultDto
{

    private const KEY_IDENTIFIER                       = 'identifier';
    private const KEY_JOB_DETAIL_DTO                   = 'jobDetailDto';
    private const KEY_SALARY_DTO                       = 'salaryDto';
    private const KEY_COMPANY_DETAIL_DTO               = 'companyDetailDto';
    private const KEY_CONTACT_DETAIL_DTO               = 'contactDetailDto';
    private const KEY_JOB_OFFER_URL                    = 'jobOfferUrl';
    private const KEY_JOB_OFFER_HOST                   = 'jobOfferHost';
    private const KEY_MENTIONED_HUMAN_LANGUAGES        = 'mentionedHumanLanguages';
    private const KEY_REMOTE_JOB_MENTIONED             = 'remoteJobMentioned';
    private const KEY_JOB_POSTED_DATE_TIME             = 'jobPostedDateTime';
    private const KEY_JOB_OFFER_LANGUAGE               = 'offerLanguage';
    private const KEY_JOB_COMPANY_COUNTRY              = 'companyCountry';
    private const KEY_JOB_COMPANY_EMPLOYEES_COUNT_HIGH = 'employeesCountHigh';
    private const KEY_JOB_COMPANY_FOUNDED_YEAR         = 'companyFoundedYear';

    /**
     * @var string|null
     */
    private ?string $identifier = null;

    /**
     * @var JobDetailDto $jobDetailDto
     */
    private JobDetailDto $jobDetailDto;

    /**
     * @var SalaryDto $salaryDto
     */
    private SalaryDto $salaryDto;

    /**
     * @var CompanyDetailDto $companyDetailDto
     */
    private CompanyDetailDto $companyDetailDto;

    /**
     * @var ContactDetailDto $contactDetailDto
     */
    private ContactDetailDto $contactDetailDto;

    /**
     * @var string $jobOfferUrl
     */
    private string $jobOfferUrl = "";

    /**
     * @var string $jobOfferHost
     */
    private string $jobOfferHost = "";

    /**
     * @var array|null $mentionedLanguages
     */
    private ?array $mentionedHumanLanguages = null;

    /**
     * @var bool $isRemoteJobMentioned
     */
    private bool $remoteJobMentioned = false;

    /**
     * @var DateTime|null
     */
    private ?DateTime $jobPostedDateTime = null;

    /**
     * @var string|null $offerLanguage
     */
    private ?string $offerLanguage = null;

    /**
     * @var string|null $companyCountry
     */
    private ?string $companyCountry = null;

    /**
     * @var int|null $foundedYear
     */
    private ?int $foundedYear = null;

    public function __construct()
    {
        $this->salaryDto        = new SalaryDto();
        $this->companyDetailDto = new CompanyDetailDto();
        $this->jobDetailDto     = new JobDetailDto();
        $this->contactDetailDto = new ContactDetailDto();
    }

    /**
     * @return JobDetailDto
     */
    public function getJobDetailDto(): JobDetailDto
    {
        return $this->jobDetailDto;
    }

    /**
     * @param JobDetailDto $jobDetailDto
     */
    public function setJobDetailDto(JobDetailDto $jobDetailDto): void
    {
        $this->jobDetailDto = $jobDetailDto;
    }

    /**
     * @return SalaryDto
     */
    public function getSalaryDto(): SalaryDto
    {
        return $this->salaryDto;
    }

    /**
     * @param SalaryDto $salaryDto
     */
    public function setSalaryDto(SalaryDto $salaryDto): void
    {
        $this->salaryDto = $salaryDto;
    }

    /**
     * @return CompanyDetailDto
     */
    public function getCompanyDetailDto(): CompanyDetailDto
    {
        return $this->companyDetailDto;
    }

    /**
     * @param CompanyDetailDto $companyDetailDto
     */
    public function setCompanyDetailDto(CompanyDetailDto $companyDetailDto): void
    {
        $this->companyDetailDto = $companyDetailDto;
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
     * @return string
     */
    public function getJobOfferHost(): string
    {
        return $this->jobOfferHost;
    }

    /**
     * @param string $jobOfferHost
     */
    public function setJobOfferHost(string $jobOfferHost): void
    {
        $this->jobOfferHost = $jobOfferHost;
    }

    /**
     * @return ContactDetailDto
     */
    public function getContactDetailDto(): ContactDetailDto
    {
        return $this->contactDetailDto;
    }

    /**
     * @param ContactDetailDto $contactDetailDto
     */
    public function setContactDetailDto(ContactDetailDto $contactDetailDto): void
    {
        $this->contactDetailDto = $contactDetailDto;
    }

    /**
     * @return array|null
     */
    public function getMentionedHumanLanguages(): ?array
    {
        return $this->mentionedHumanLanguages;
    }

    /**
     * @param array|null $mentionedHumanLanguages
     */
    public function setMentionedHumanLanguages(?array $mentionedHumanLanguages): void
    {
        $this->mentionedHumanLanguages = $mentionedHumanLanguages;
    }

    /**
     * @return bool
     */
    public function isRemoteJobMentioned(): bool
    {
        return $this->remoteJobMentioned;
    }

    /**
     * @param bool $remoteJobMentioned
     */
    public function setRemoteJobMentioned(bool $remoteJobMentioned): void
    {
        $this->remoteJobMentioned = $remoteJobMentioned;
    }

    /**
     * @return DateTime|null
     */
    public function getJobPostedDateTime(): ?DateTime
    {
        return $this->jobPostedDateTime;
    }

    /**
     * @param DateTime|null $jobPostedDateTime
     */
    public function setJobPostedDateTime(?DateTime $jobPostedDateTime): void
    {
        $this->jobPostedDateTime = $jobPostedDateTime;
    }

    /**
     * @return string|null
     */
    public function getOfferLanguage(): ?string
    {
        return $this->offerLanguage;
    }

    /**
     * @param string|null $offerLanguage
     */
    public function setOfferLanguage(?string $offerLanguage): void
    {
        $this->offerLanguage = $offerLanguage;
    }

    /**
     * @return string|null
     */
    public function getCompanyCountry(): ?string
    {
        return $this->companyCountry;
    }

    /**
     * @param string|null $companyCountry
     */
    public function setCompanyCountry(?string $companyCountry): void
    {
        $this->companyCountry = $companyCountry;
    }

    /**
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string|null $identifier
     */
    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return int|null
     */
    public function getFoundedYear(): ?int
    {
        return $this->foundedYear;
    }

    /**
     * @param int|null $foundedYear
     */
    public function setFoundedYear(?int $foundedYear): void
    {
        $this->foundedYear = $foundedYear;
    }

    /**
     * Returns dto created from search result entity
     *
     * @param JobSearchResult $jobSearchResult
     * @param Email|null      $applicationEmail
     * @param bool            $useShortDescription
     *
     * @return static
     */
    public static function fromJobSearchResultEntity(JobSearchResult $jobSearchResult, ?Email $applicationEmail, bool $useShortDescription = false): self
    {
        $dto = new SearchResultDto();

        $usedDescription = ($useShortDescription ? $jobSearchResult->getShortJobDescription() : $jobSearchResult->getJobDescription());

        $dto->setIdentifier($jobSearchResult->getId());
        $dto->setMentionedHumanLanguages($jobSearchResult->getMentionedHumanLanguages());
        $dto->getJobDetailDto()->setJobTitle($jobSearchResult->getJobTitle());
        $dto->getJobDetailDto()->setJobDescription($usedDescription);
        $dto->setJobOfferUrl($jobSearchResult->getJobOfferUrl());
        $dto->setJobOfferHost($jobSearchResult->getJobOfferHost());
        $dto->getSalaryDto()->setSalaryMin($jobSearchResult->getSalaryMin());
        $dto->getSalaryDto()->setSalaryMax($jobSearchResult->getSalaryMax());
        $dto->getSalaryDto()->setSalaryAverage($jobSearchResult->getSalaryAverage());
        $dto->getContactDetailDto()->setPhoneNumber($jobSearchResult->getCompanyBranch()->getFirstPhoneNumber());
        $dto->setRemoteJobMentioned($jobSearchResult->isRemoteJobMentioned());
        $dto->setJobPostedDateTime($jobSearchResult->getJobPostedDateTime());
        $dto->getCompanyDetailDto()->setCompanyName($jobSearchResult->getCompany()?->getName());
        $dto->getCompanyDetailDto()->setWebsiteUrl($jobSearchResult->getCompany()?->getWebsite());
        $dto->setOfferLanguage($jobSearchResult->getOfferLanguage());
        $dto->setCompanyCountry($jobSearchResult?->getCompanyBranch()?->getLocation()?->getCountry());
        $dto->setFoundedYear($jobSearchResult->getCompany()->getFoundedYear());

        if (!empty($applicationEmail)) {
            $dto->getContactDetailDto()->setEmail($applicationEmail->getAddress());
            $dto->getContactDetailDto()->setEmailFromJobOffer($applicationEmail->isFromJobOffer($jobSearchResult));
        }

        foreach($jobSearchResult->getLocations() as $locationEntity){
            $dto->getCompanyDetailDto()->addCompanyLocation($locationEntity->getName());
        }

        return $dto;
    }

    /**
     * Return array representation of the dto
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::KEY_IDENTIFIER                       => $this->getIdentifier(),
            self::KEY_JOB_DETAIL_DTO                   => $this->getJobDetailDto()->toArray(),
            self::KEY_SALARY_DTO                       => $this->getSalaryDto()->toArray(),
            self::KEY_COMPANY_DETAIL_DTO               => $this->getCompanyDetailDto()->toArray(),
            self::KEY_CONTACT_DETAIL_DTO               => $this->getContactDetailDto()->toArray(),
            self::KEY_JOB_OFFER_URL                    => $this->getJobOfferUrl(),
            self::KEY_JOB_OFFER_HOST                   => $this->getJobOfferHost(),
            self::KEY_MENTIONED_HUMAN_LANGUAGES        => $this->getMentionedHumanLanguages(),
            self::KEY_REMOTE_JOB_MENTIONED             => $this->isRemoteJobMentioned(),
            self::KEY_JOB_POSTED_DATE_TIME             => ( is_null($this->getJobPostedDateTime()) ? '' : $this->getJobPostedDateTime()->format("Y-m-d H:i:s")),
            self::KEY_JOB_OFFER_LANGUAGE               => $this->getOfferLanguage(),
            self::KEY_JOB_COMPANY_COUNTRY              => $this->getCompanyCountry(),
            self::KEY_JOB_COMPANY_FOUNDED_YEAR         => $this->getFoundedYear(),
        ];
    }

}