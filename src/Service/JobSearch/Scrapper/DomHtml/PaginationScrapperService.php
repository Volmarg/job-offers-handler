<?php

namespace JobSearcher\Service\JobSearch\Scrapper\DomHtml;

use Exception;
use JobSearcher\DTO\JobSearch\DOM\DomElementConfigurationDto;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BasePaginationOfferDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;
use JobSearcher\Exception\Configuration\DomHtml\ConfigurationNotFoundException;
use JobSearcher\Service\TypeProcessor\StringTypeProcessor;
use Symfony\Component\DomCrawler\Crawler;

class PaginationScrapperService
{
    private MainConfigurationDto $mainConfigurationDto;
    private ScrapperService $scrapperService;

    /**
     * @return MainConfigurationDto
     */
    public function getMainConfigurationDto(): MainConfigurationDto
    {
        return $this->mainConfigurationDto;
    }

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     */
    public function setMainConfigurationDto(MainConfigurationDto $mainConfigurationDto): void
    {
        $this->mainConfigurationDto = $mainConfigurationDto;
    }

    /**
     * @return ScrapperService
     */
    public function getScrapperService(): ScrapperService
    {
        return $this->scrapperService;
    }

    /**
     * @param ScrapperService $scrapperService
     */
    public function setScrapperService(ScrapperService $scrapperService): void
    {
        $this->scrapperService = $scrapperService;
    }

    /**
     * Get the search page pagination results + filter them out etc.
     *
     * @param Crawler               $crawler
     * @param JobSearchParameterBag $searchParams
     *
     * @return BasePaginationOfferDto[]
     * @throws Exception
     */
    public function scrap(Crawler $crawler, JobSearchParameterBag $searchParams): array
    {
        if ($this->noResults($crawler)) {
            return [];
        }

        if (!$this->areLocationResultsValid($crawler, $searchParams)) {
            return [];
        }

        $paginationResults = $this->getPaginationResults($crawler);
        $filteredResults   = $this->filterDtos($paginationResults);

        return $filteredResults;
    }

    /**
     * Will attempt to obtain the results from search result pagination
     *
     * @param Crawler $crawler
     *
     * @return array
     * @throws Exception
     */
    private function getPaginationResults(Crawler $crawler): array
    {
        $paginationBlocksSelector = $this->mainConfigurationDto->getDomElementSelectorAndAttributeConfiguration(DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_OFFER_BLOCK);
        $paginationBlocksCrawler  = $crawler->filter($paginationBlocksSelector->getCssSelector());

        $arrayOfDto               = [];
        foreach ($paginationBlocksCrawler->getIterator() as $node) {
            $dto         = new BasePaginationOfferDto();
            $nodeCrawler = new Crawler($node);

            // special handling of case when the block node is a link itself, then the offer url is fetched directly from the block / card Node
            if ($this->mainConfigurationDto->hasDomSelectorForPurpose(DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_LINK_TO_DETAIL_PAGE)) {
                $foundUri = $this->getScrapperService()->scrapDataWithCrawlerAndGetFirstMatch($nodeCrawler, DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_LINK_TO_DETAIL_PAGE);
            } else {
                $foundUri = (!empty($node->textContent) ? $node->textContent : "");
                if ($paginationBlocksSelector->isGetDataFromAttribute()) {
                    $foundUri = $node->attributes->getNamedItem($paginationBlocksSelector->getTargetAttributeName())->nodeValue;
                }
            }

            $dto->setJobOfferUrl($foundUri);
            foreach ($this->getMainConfigurationDto()->getDetailPageLinkExcludedPatterns() as $excludedPattern) {
                if( preg_match("#" . preg_quote($excludedPattern) . "#", $foundUri) ){
                    $dto->setExcludedFromScrapping(true);
                    break;
                }
            }

            $companyName = StringTypeProcessor::trim(
                $this->getScrapperService()->scrapDataWithCrawlerAndGetFirstMatch($nodeCrawler, DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_COMPANY_NAME)
            );

            $jobTitle = StringTypeProcessor::trim(
                $this->getScrapperService()->scrapDataWithCrawlerAndGetFirstMatch($nodeCrawler,DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_JOB_TITLE)
            );

            $jobDescription = "";
            if ($this->mainConfigurationDto->hasDomSelectorForPurpose(DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_JOB_DESCRIPTION)) {
                $jobDescription = StringTypeProcessor::trim(
                    $this->getScrapperService()->scrapDataWithCrawlerAndGetFirstMatch($nodeCrawler,DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_JOB_DESCRIPTION)
                );
            }

            try { // that's wanted because not all services got company location on pagination etc.
                $companyLocation = StringTypeProcessor::trim(
                    $this->getScrapperService()->scrapDataWithCrawlerAndGetFirstMatch($nodeCrawler, DomElementConfigurationDto::PURPOSE_PAGINATION_PAGE_COMPANY_LOCATION)
                );
            } catch (ConfigurationNotFoundException) {
                $companyLocation = null;
            }

            try { // that's wanted because not all services got posted date present on pagination
                $postedDate = StringTypeProcessor::trim(
                    $this->getScrapperService()->scrapDataWithCrawlerAndGetFirstMatch($nodeCrawler, DomElementConfigurationDto::PURPOSE_PAGINATION_POSTED_DATE)
                );
            } catch (ConfigurationNotFoundException) {
                $postedDate = null;
            }

            $dto->setPostedDateTimeString($postedDate);
            $dto->setCompanyName($companyName);
            $dto->setJobOfferTitle($jobTitle);
            $dto->setJobOfferDescription($jobDescription);
            $dto->setCompanyLocation($companyLocation);

            $arrayOfDto[] = $dto;
        }

        return $arrayOfDto;
    }

    /**
     * Filter out found pagination results.
     * Will for example wipe duplicates.
     *
     * @param array $arrayOfDto BasePaginationOfferDto[]
     *
     * @return BasePaginationOfferDto[]
     */
    private function filterDtos(array $arrayOfDto): array
    {
        $filteredArrayDto         = [];
        $processedDetailPageLinks = [];
        $processedOffersHashes    = [];
        foreach ($arrayOfDto as $dto) {
            $offerHash = md5($dto->getCompanyName() . $dto->getJobOfferTitle());

            // duplicated job offers on page
            if (
                    in_array($dto->getJobOfferUrl(), $processedDetailPageLinks) // same offer but different link
                ||  in_array($offerHash, $processedOffersHashes)                // same company and same job title
            ){
                continue;
            }

            $processedDetailPageLinks[] = $dto->getJobOfferUrl();
            $processedOffersHashes[]    = $offerHash;
            foreach ($this->getMainConfigurationDto()->getDetailPageLinkReplaceRegexRules() as $replacePattern => $replaceTo) {
                $replacedLink = preg_replace($replacePattern, $replaceTo, $dto->getJobOfferUrl());
                $dto->setJobOfferUrl($replacedLink);
            }

            $filteredArrayDto[] = $dto;
        }

        return $filteredArrayDto;
    }

    /**
     * Will check if any kind of "no results" block is on the page.
     * That's needed because services will sometimes list some offers despite none matching given criteria.
     * It's often not possible to distinguish "extra offers" from "the one that should be there"
     *
     * @param Crawler $crawler
     *
     * @return bool
     * @throws Exception
     */
    private function noResults(Crawler $crawler): bool
    {
        try {
            $selectorDto = $this->mainConfigurationDto->getDomElementSelectorAndAttributeConfiguration(DomElementConfigurationDto::PURPOSE_PAGINATION_NO_RESULTS);
        } catch (ConfigurationNotFoundException) {
            // no selector configured - meaning that check for "no offers" is not supported
            // so this says "there are results"
            return false;
        }

        $blocksCrawler = $crawler->filter($selectorDto->getCssSelector());

        return ($blocksCrawler->getIterator()->count() != 0);
    }

    /**
     * In some cases the location based results are invalid,
     * This will check if such is the case.
     *
     * If configuration for this check does not exist or no location is provided
     * then returning true, else the proper check is triggered.
     *
     * @param Crawler               $crawler
     * @param JobSearchParameterBag $searchParams
     *
     * @return bool
     *
     * @throws Exception
     */
    private function areLocationResultsValid(Crawler $crawler, JobSearchParameterBag $searchParams): bool
    {
        if (empty($searchParams->getLocation())) {
            return true;
        }

        try {
            $selectorDto = $this->mainConfigurationDto->getDomElementSelectorAndAttributeConfiguration(DomElementConfigurationDto::PURPOSE_PAGINATION_VALID_LOCATION_RESULTS);
        } catch (ConfigurationNotFoundException) {
            // no selector configured - meaning that check for "are location based results" is not supported
            // so this says "is valid"
            return true;
        }

        foreach ($crawler->filter($selectorDto->getCssSelector()) as $node) {
            $html = (new Crawler($node))->html();

            return ScrapperService::callAllowedMethod(
                $selectorDto->getCalledMethodName(),
                [$html, $this->mainConfigurationDto, ...$selectorDto->getCalledMethodArgs()]
            );
        }

        // not a single matching node was found, assuming that location data is fine then
        return true;
    }
}