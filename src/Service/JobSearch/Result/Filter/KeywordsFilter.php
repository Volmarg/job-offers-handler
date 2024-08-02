<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use DataParser\Service\Parser\Text\TextParser;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\DTO\JobService\SearchResultAnalyze\KeywordsParsingInformationDto;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Service\JobAnalyzer\JobSearchResultAnalyzerInterface;
use JobSearcher\Service\JobAnalyzer\JobSearchResultAnalyzerService;

/**
 * Check if offer matches keywords criteria:
 * - included / excluded keywords check
 */
class KeywordsFilter extends BaseFilter implements OfferFilterInterface
{
    private const KEY_TOTAL_COUNT = "totalCount";
    private const MINIMUM_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES  = 40;
    private const DEMANDED_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES = 80;

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function filter(): array
    {
        $filteredOffers = [];
        foreach ($this->getOffers() as $offer) {

            $wrappedIncludedKeywordsDto = JobSearchResultAnalyzerService::doWrapKeywordsInDescription(
                JobSearchResultAnalyzerInterface::KEYWORDS_TYPE_INCLUDED,
                $this->getFilterDto()->getIncludedKeywords(),
                $offer->getJobDescription()
            );

            $wrappedExcludedKeywordsDto = JobSearchResultAnalyzerService::doWrapKeywordsInDescription(
                JobSearchResultAnalyzerInterface::KEYWORDS_TYPE_EXCLUDED,
                $this->getFilterDto()->getExcludedKeywords(),
                $wrappedIncludedKeywordsDto->getStringAfterApplyingKeywords()
            );

            $hasKeywords = true;
            if (!empty($this->getFilterDto()->getIncludedKeywords())) {
                $hasKeywords = $this->hasKeywords($this->getFilterDto()->getIncludedKeywords(), $this->getFilterDto()->getIncludedKeywordsCheckMode(), $offer);
            }

            $hasMandatoryIncludedKeywords = true;
            if (!empty($this->getFilterDto()->getMandatoryIncludedKeywords())) {
                $hasMandatoryIncludedKeywords = $this->hasKeywords($this->getFilterDto()->getMandatoryIncludedKeywords(), $this->getFilterDto()->getMandatoryIncludedKeywordsCheckMode(), $offer);
            }

            $hasExcludedKeywords = $this->hasKeywords($this->getFilterDto()->getExcludedKeywords(), $this->getFilterDto()->getExcludedKeywordsCheckMode(), $offer);

            $percentageOfExcludedKeywordsOverAllTotallyFound = $this->calculatePercentageOfExcludedKeywordsOverAllTotallyFound($wrappedIncludedKeywordsDto, $wrappedExcludedKeywordsDto);
            $areKeywordsMatching                             = $this->areKeywordsMatching(
                $percentageOfExcludedKeywordsOverAllTotallyFound,
                $hasKeywords,
                $hasMandatoryIncludedKeywords,
                $hasExcludedKeywords
            );

            if ($areKeywordsMatching) {
                $filteredOffers[] = $offer;
            }
        }

        return $filteredOffers;
    }

    /**
     * Check if all/any mandatory keywords are included
     *
     * @param array $keywords
     * @param string $checkMode
     * @param JobSearchResult $offer
     *
     * @return bool
     */
    private function hasKeywords(array $keywords, string $checkMode, JobSearchResult $offer): bool
    {
        if (empty($keywords)) {
            return false;
        }

        foreach($keywords as $keyword){
            switch($checkMode)
            {
                case JobOfferFilterDto::KEYWORDS_CHECK_MODE_ALL:
                    {
                        if(
                                !TextParser::hasWord($offer->getJobDescription(), $keyword)
                            &&  !TextParser::hasWord($offer->getJobTitle(), $keyword)
                        ){
                            return false;
                        }
                    }
                    break;

                case JobOfferFilterDto::KEYWORDS_CHECK_MODE_ANY:
                    {
                        if(
                                TextParser::hasWord($offer->getJobDescription(), $keyword)
                            ||  TextParser::hasWord($offer->getJobTitle(), $keyword)
                        ){
                            return true;
                        }
                    }
                    break;

                default:
                    throw new \LogicException("This mode is not supported: {$checkMode}");
            }
        }

        if (JobOfferFilterDto::KEYWORDS_CHECK_MODE_ALL === $checkMode) {
            return true;
        }

        return false;
    }

    /**
     * Will calculate percentage of excluded keywords over all totally found
     *
     * @param KeywordsParsingInformationDto $wrappedIncludedKeywordsDto
     * @param KeywordsParsingInformationDto $wrappedExcludedKeywordsDto
     * @return int
     */
    private function calculatePercentageOfExcludedKeywordsOverAllTotallyFound(
        KeywordsParsingInformationDto $wrappedIncludedKeywordsDto,
        KeywordsParsingInformationDto $wrappedExcludedKeywordsDto
    ): int
    {
        $countOfIncludedKeywords = $wrappedIncludedKeywordsDto->getCountOfKeywords();
        $countOfExcludedKeywords = $wrappedExcludedKeywordsDto->getCountOfKeywords();

        $allFoundIncludedKeywordsCount   = $countOfIncludedKeywords[self::KEY_TOTAL_COUNT] ?? 0;
        $allFoundExcludedKeywordsCount   = $countOfExcludedKeywords[self::KEY_TOTAL_COUNT] ?? 0;
        $allKeywordsCountTotally         = $allFoundIncludedKeywordsCount + $allFoundExcludedKeywordsCount;

        if(
                0 === $allFoundIncludedKeywordsCount
            &&  0 === $allFoundExcludedKeywordsCount
        ){
            return 0;
        }

        $percentageCount = $allFoundExcludedKeywordsCount/$allKeywordsCountTotally * 100;
        return (int)$percentageCount;
    }

    /**
     * Get the end keywords filter check result
     *
     * @param float $percentageOfExcludedKeywordsOverAllTotallyFound
     * @param bool  $hasKeywords
     * @param bool  $hasMandatoryIncludedKeywords
     * @param bool  $hasExcludedKeywords
     *
     * @return bool
     */
    private function areKeywordsMatching(
        float $percentageOfExcludedKeywordsOverAllTotallyFound,
        bool  $hasKeywords,
        bool  $hasMandatoryIncludedKeywords,
        bool  $hasExcludedKeywords
    ): bool
    {
        if(
                !empty($this->getFilterDto()->getMandatoryIncludedKeywords())
            &&  !$hasMandatoryIncludedKeywords

        ){
            return false;
        }

        if(
                !empty($this->getFilterDto()->getExcludedKeywords())
            &&  $hasExcludedKeywords
        ){
            return false;
        }elseif(
                $this->getFilterDto()->getExcludedKeywordsCheckMode() === JobOfferFilterDto::KEYWORDS_CHECK_MODE_PERCENTAGE
            &&  $percentageOfExcludedKeywordsOverAllTotallyFound >= self::MINIMUM_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES
            &&  $percentageOfExcludedKeywordsOverAllTotallyFound <= self::DEMANDED_PERCENTAGE_OF_INCLUDED_KEYWORDS_OVER_THE_EXCLUDED_ONES
        ){
            return true;
        }

        if (!$hasKeywords) {
            return false;
        }

        return true;
    }
}