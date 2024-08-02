<?php

namespace JobSearcher\Service\JobAnalyzer;

use JobSearcher\DTO\Api\Transport\JobOfferAnalyseResultDto;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\DTO\JobService\SearchResultAnalyze\KeywordsParsingInformationDto;
use JobSearcher\DTO\JobService\SearchResultAnalyze\SearchResultAnalyzedDto;
use JobSearcher\Service\DOM\DomTagWrapperService;
use Exception;

/**
 * Handles all variety of analyzes for the job offer search result,
 * The statuses set in here are used on front to determine which elements of offer card
 * will be display etc. {@see JobOfferAnalyseResultDto::buildFromProcessedResult()}
 */
class JobSearchResultAnalyzerService implements JobSearchResultAnalyzerInterface
{

    /**
     * @var SearchResultDto $searchResultDTO
     */
    private SearchResultDto $searchResultDTO;

    /**
     * @var JobOfferFilterDto $jobOfferFilterDTO
     */
    private JobOfferFilterDto $jobOfferFilterDTO;

    /**
     * @param SearchResultDto $searchResultDTO
     * @param JobOfferFilterDto $jobOfferFilterDTO
     */
    public function __construct(SearchResultDto $searchResultDTO, JobOfferFilterDto $jobOfferFilterDTO)
    {
        $this->searchResultDTO   = $searchResultDTO;
        $this->jobOfferFilterDTO = $jobOfferFilterDTO;
    }

    /**
     * Will handle the whole analyze of search result providing some flags helpful on the front etc.
     *
     * @throws Exception
     */
    public function analyzeSearchResult(): SearchResultAnalyzedDto
    {
        $this->jobOfferFilterDTO->validateSelf();

        $hasPhone  = !empty($this->searchResultDTO->getContactDetailDto()->getPhoneNumber());
        $hasMail   = !empty($this->searchResultDTO->getContactDetailDto()->getEmail());
        $hasSalary = (
                $this->searchResultDTO->getSalaryDto()->getSalaryMin()
            ||  $this->searchResultDTO->getSalaryDto()->getSalaryAverage()
        );

        $hasJobDateTimePosted       = !empty($this->searchResultDTO->getJobPostedDateTime());
        $hasHumanLanguagesMentioned = !empty($this->searchResultDTO->getMentionedHumanLanguages());
        $hasCompanyLocation         = !empty($this->searchResultDTO->getCompanyDetailDto()->getCompanyLocations());

        $jobSearchResultAnalyzed = new SearchResultAnalyzedDto();
        $jobSearchResultAnalyzed->setHasSalary($hasSalary);
        $jobSearchResultAnalyzed->setHasPhone($hasPhone);
        $jobSearchResultAnalyzed->setHasMail($hasMail);
        $jobSearchResultAnalyzed->setHasJobDateTimePostedInformation($hasJobDateTimePosted);
        $jobSearchResultAnalyzed->setHasCompanyLocation($hasCompanyLocation);
        $jobSearchResultAnalyzed->setAnyHumanLanguageMentioned($hasHumanLanguagesMentioned);

        return $jobSearchResultAnalyzed;
    }

    /**
     * Handles wrapping keywords in the description
     *
     * @param string $keywordType
     * @param array $keywords
     * @param string $jobDescription
     * @return KeywordsParsingInformationDto
     * @throws Exception
     */
    public static function doWrapKeywordsInDescription(string $keywordType, array $keywords, string $jobDescription): KeywordsParsingInformationDto
    {
        $keywordsParsingInformation = new KeywordsParsingInformationDto();
        $keywordsCount              = [];

        $keywordsCount[self::KEY_TOTAL_COUNT] = 0;

        if (empty($jobDescription)) {
            $keywordsParsingInformation->setCountOfKeywords($keywordsCount);
            return $keywordsParsingInformation;
        }

        foreach($keywords as $keyword){

            switch($keywordType){
                case self::KEYWORDS_TYPE_EXCLUDED:
                {
                    $wrappedKeyword = DomTagWrapperService::wrapIntoClassTagJobOfferExcluded($keyword);
                }
                break;

                case self::KEYWORDS_TYPE_INCLUDED:
                {
                    $wrappedKeyword = DomTagWrapperService::wrapIntoClassTagJobOfferIncluded($keyword);
                }
                break;

                case self::KEYWORDS_TYPE_INCLUDED_MANDATORY:
                {
                    $wrappedKeyword = DomTagWrapperService::wrapIntoClassTagJobOfferIncludedMandatory($keyword);
                }
                break;

                default:
                {
                    throw new Exception("This keyword type is not supported {$keywordType}");
                }
            }

            $keywordOccurrenceCountInString = substr_count(mb_strtoupper($jobDescription), mb_strtoupper($keyword)); // case-insensitive substr
            if(0 !== $keywordOccurrenceCountInString){

                $keywordsCount[$keyword] = (
                array_key_exists($keyword, $keywordsCount)
                    ? $keywordsCount[$keyword]++
                    : 1
                );

                $keywordsCount[self::KEY_TOTAL_COUNT] += $keywordOccurrenceCountInString;
                $jobDescription = str_ireplace($keyword, $wrappedKeyword, $jobDescription);
                continue;
            }

            $keywordsCount[$keyword] = 0;
        }

        $keywordsParsingInformation->setCountOfKeywords($keywordsCount);
        $keywordsParsingInformation->setStringAfterApplyingKeywords($jobDescription);

        return $keywordsParsingInformation;
    }

}