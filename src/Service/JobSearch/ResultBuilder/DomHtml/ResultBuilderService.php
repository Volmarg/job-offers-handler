<?php

namespace JobSearcher\Service\JobSearch\ResultBuilder\DomHtml;

use DataParser\Service\Parser\Date\DateParser;
use Exception;
use JobSearcher\DTO\JobSearch\DOM\DomElementConfigurationDto;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\DTO\JobService\CrawlerWithPaginationResultDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Service\DOM\TagsCleanerService;
use JobSearcher\Service\JobSearch\Extractor\DomHtml\ExtractorService;
use JobSearcher\Service\JobSearch\Scrapper\BaseScrapperService;
use JobSearcher\Service\JobSearch\Scrapper\DomHtml\ScrapperService;
use SmtpEmailValidatorBundle\Service\SmtpValidator;

/**
 * Handles building {@see SearchResultDto} for {@see ExtractorService}
 * Was added in order to reduce the bloating code of {@see ExtractorService::buildSearchResults()}
 */
class ResultBuilderService
{
    private const MAX_DISTANCE_SEARCH_KM = 5;

    /**
     * @var MainConfigurationDto $mainConfigurationDto
     */
    private MainConfigurationDto $mainConfigurationDto;

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     */
    public function setMainConfigurationDto(MainConfigurationDto $mainConfigurationDto): void
    {
        $this->mainConfigurationDto = $mainConfigurationDto;
    }

    public function __construct(
        private readonly ScrapperService $scrapperService,
        private readonly SmtpValidator   $smtpValidator,
    ){}

    /**
     * @param CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto
     * @param JobSearchParameterBag          $searchParams
     *
     * @return SearchResultDto
     *
     * @throws Exception
     */
    public function build(CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto, JobSearchParameterBag $searchParams): SearchResultDto
    {
        # todo: get search params bag and use location from it if none was found on offer details etc.
        $searchResult = new SearchResultDto();

        $this->setOfferDetails($searchResult, $crawlerWithPaginationResultDto);
        $this->setSalaryData($searchResult, $crawlerWithPaginationResultDto);
        $this->setCompanyData($searchResult, $crawlerWithPaginationResultDto, $searchParams);
        $this->setLinks($searchResult, $crawlerWithPaginationResultDto);
        $this->setOthers($searchResult, $crawlerWithPaginationResultDto);

        # this is set later on purposed as it must have description set first
        $this->setContactDetails($searchResult, $crawlerWithPaginationResultDto);

        return $searchResult;
    }

    /**
     * @param SearchResultDto $searchResult
     * @param CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto
     *
     * @throws Exception
     */
    private function setContactDetails(SearchResultDto $searchResult, CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto): void
    {
        $crawler        = $crawlerWithPaginationResultDto->getCrawler();
        $contactPhone   = $this->scrapperService->scrapDataWithCrawlerAndGetFirstMatch($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_CONTACT_DETAIL_COMPANY_PHONE);
        $contactEmail   = $this->scrapperService->scrapDataWithCrawlerAndGetFirstMatch($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_CONTACT_DETAIL_COMPANY_EMAIL);

        if (empty($contactEmail)) {
            $contactEmail = BaseScrapperService::extractEmailFromString($searchResult->getJobDetailDto()->getJobDescription()) ?? "";
        }

        if (!empty($contactEmail)) {
            $contactEmail = $this->smtpValidator->doBaseValidation($contactEmail) ? $contactEmail : "";
        }


        $searchResult->getContactDetailDto()->setEmail($contactEmail);
        $searchResult->getContactDetailDto()->setPhoneNumber($contactPhone);
    }

    /**
     * @param SearchResultDto $searchResult
     * @param CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto
     *
     * @throws Exception
     */
    private function setSalaryData(SearchResultDto $searchResult, CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto): void
    {
        $crawler        = $crawlerWithPaginationResultDto->getCrawler();
        $salaryMin      = $this->scrapperService->scrapSalaryWithCrawler($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_SALARY_MIN);
        $salaryMax      = $this->scrapperService->scrapSalaryWithCrawler($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_SALARY_MAX);
        $salaryAverage  = $this->scrapperService->scrapSalaryWithCrawler($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_SALARY_ESTIMATED);

        $searchResult->getSalaryDto()->setSalaryAverage($salaryAverage);
        $searchResult->getSalaryDto()->setSalaryMin($salaryMin);
        $searchResult->getSalaryDto()->setSalaryMax($salaryMax);
    }

    /**
     * @param SearchResultDto                $searchResult
     * @param CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto
     * @param JobSearchParameterBag          $searchParams
     *
     * @throws Exception
     */
    private function setCompanyData(SearchResultDto $searchResult, CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto, JobSearchParameterBag $searchParams): void
    {
        $crawler = $crawlerWithPaginationResultDto->getCrawler();

        $companyName  = $this->scrapperService->scrapDataWithCrawlerAndGetFirstMatch($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_COMPANY_WORKPLACE_DATA_COMPANY_NAME);
        $jobLocations = $this->scrapperService->scrapDataWithCrawlerAndDomConfiguration($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_COMPANY_WORKPLACE_DATA_LOCATION);

        // sometimes it's hard to extract company name from the detail so trying to use the pagination based data
        if (empty($companyName)) {
            $companyName = $crawlerWithPaginationResultDto->getPaginationOfferDto()->getCompanyName();
        }

        // sometimes it's hard to extract company location from the detail so trying to use the pagination based data
        if (empty($jobLocations)) {
            $companyLocation = $crawlerWithPaginationResultDto->getPaginationOfferDto()->getCompanyLocation();
            if (!empty($companyLocation)) {
                $jobLocations[] = $companyLocation;
            }
        }

        # the location is still empty, so if search was made with usage of location then take that location
        if (
                empty($companyLocation)
            && !empty($searchParams->getLocation())
            &&
            (
                    !$searchParams->isDistanceSet()
                ||  $searchParams->getDistance() <= self::MAX_DISTANCE_SEARCH_KM // because it might be already different location
            )
        ) {
            $jobLocations[] = $searchParams->getLocation();
        }

        foreach ($jobLocations as &$jobLocation) {
            $jobLocation = trim($jobLocation);
        }

        $searchResult->getCompanyDetailDto()->setCompanyLocations($jobLocations);
        $searchResult->getCompanyDetailDto()->setCompanyName(trim($companyName));
    }

    /**
     * @param SearchResultDto $searchResult
     * @param CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto
     */
    private function setLinks(SearchResultDto $searchResult, CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto): void
    {
        $crawler = $crawlerWithPaginationResultDto->getCrawler();
        $searchResult->setJobOfferHost($this->mainConfigurationDto->getHost());
        $searchResult->setJobOfferUrl($crawler->getUri());
    }

    /**
     * @param SearchResultDto $searchResult
     * @param CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto
     *
     * @throws Exception
     */
    private function setOthers(SearchResultDto $searchResult, CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto): void
    {
        $crawler = $crawlerWithPaginationResultDto->getCrawler();

        // that one is based on checking if some "remote" indicating words are mentioned somewhere in text (description etc.)
        $isRemoteJobMentioned = BaseScrapperService::scrapMentionedThatRemoteIsPossible(
            $searchResult->getJobDetailDto()->getJobDescription(),
            $searchResult->getJobDetailDto()->getJobTitle(),
            $searchResult->getCompanyDetailDto()->getCompanyLocations(),
            $this->mainConfigurationDto->getSupportedCountry()
        );

        if (!$isRemoteJobMentioned) {
            // this one is based also on checking for "remote" word mentioned but in special selector which should have this information
            $isRemoteJobMentioned = !empty($this->scrapperService->scrapDataWithCrawlerAndGetFirstMatch(
                $crawler,
                DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_REMOTE_WORK_POSSIBLE
            ));
        }

        $jobPostedDateTimeString = $crawlerWithPaginationResultDto->getPaginationOfferDto()->getPostedDateTimeString();
        if (empty($jobPostedDateTimeString)) {
            $jobPostedDateTimeString = $this->scrapperService->scrapDataWithCrawlerAndGetFirstMatch($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_OFFER_DETAIL_JOB_POSTED_DATE_TIME);
        }

        $jobPostedDateTime = DateParser::parseDateFromString($jobPostedDateTimeString);

        $searchResult->setRemoteJobMentioned($isRemoteJobMentioned);
        $searchResult->setJobPostedDateTime($jobPostedDateTime);
    }

    /**
     * @param SearchResultDto $searchResult
     * @param CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto
     * @throws Exception
     */
    private function setOfferDetails(SearchResultDto $searchResult, CrawlerWithPaginationResultDto $crawlerWithPaginationResultDto): void
    {
        $crawler  = $crawlerWithPaginationResultDto->getCrawler();
        $jobTitle = $this->scrapperService->scrapDataWithCrawlerAndGetFirstMatch($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_OFFER_DETAIL_JOB_NAME);
        if (empty($jobTitle)) {
            $jobTitle = $crawlerWithPaginationResultDto->getPaginationOfferDto()->getJobOfferTitle();
        }

        $jobDescription = $this->scrapperService->scrapDataWithCrawlerAndGetFirstMatch($crawler, DomElementConfigurationDto::PURPOSE_DETAIL_PAGE_OFFER_DETAIL_JOB_DESCRIPTION);
        $jobDescription = TagsCleanerService::removeTags($jobDescription);

        if (empty($jobDescription)) {
            $jobDescription = $crawlerWithPaginationResultDto->getPaginationOfferDto()->getJobOfferDescription();
        }

        $searchResult->getJobDetailDto()->setJobDescription(nl2br(trim($jobDescription)));
        $searchResult->getJobDetailDto()->setJobTitle(trim($jobTitle));
    }

}