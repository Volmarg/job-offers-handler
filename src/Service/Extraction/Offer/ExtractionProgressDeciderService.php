<?php

namespace JobSearcher\Service\Extraction\Offer;

use Exception;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use JobSearcher\Service\JobSearch\OfferExtractionLimiterService;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * This service exists to explicitly calculate the value for: {@see JobOfferExtraction::$percentageDone}
 */
class ExtractionProgressDeciderService
{
    /**
     * Normally the extraction progress is taken from the difference of "how many configurations should be handled"
     * and how many configurations "were handled".
     *
     * However, if the percentage is below given value then other rule will be used:
     * - "how many offers were found overall"
     */
    private const MIN_EXTRACTION_PROGRESS_PERCENTAGE_FOR_QUANTITY_PROGRESS_CHECK = 70;

    /**
     * This value is based on extractions made so far.
     * It's related to:
     * - {@see AbstractJobSearchCommand::MIN_EXTRACTION_PROGRESS_PERCENTAGE_FOR_QUANTITY_PROGRESS_CHECK}
     *
     * Meaning that if given amount of offers were found then the extraction will be considered 100% done.
     * That's because it's really high amount of offers anyway.
     *
     * Value should be reasonably high, as it's unfair to expect the run to be 100% done with just 5 offers returned,
     * if some configuration was skipped.
     *
     * However, some searches will return very low amount of offers if user will for example look for keywords such as "dsfsdfsdf".
     */
    private const MIN_OFFERS_FOUND_FOR_QUANTITY_BASED_100_PERCENT = 150;

    private const HIGHEST_OFFERS_COUNT_TO_CONSIDER_COMPARING_OTHER_EXTRACTIONS = 20;

    private const MAX_PERCENTAGE_DIFFERENCE_BETWEEN_OTHER_EXTRACTIONS_FOR_KW = 20;

    private JobOfferExtraction $extraction;
    private ?string $country;
    private array $targetSources;
    private array $targetConfigurationNames;
    private array $keywords;

    public function __construct(
        private readonly ConfigurationReader          $configurationReader,
        private readonly JobOfferExtractionRepository $jobOfferExtractionRepository,
        private readonly LoggerInterface              $logger
    ) {
    }

    /**
     * @param JobOfferExtraction $extraction
     * @param array              $keywords
     * @param string|null        $country
     * @param array              $targetSources
     * @param array              $targetConfigurationNames
     *
     * @return void
     */
    public function init(
        JobOfferExtraction $extraction,
        array              $keywords,
        ?string            $country = null,
        array              $targetSources = [],
        array              $targetConfigurationNames = [],
    ): void
    {
        $this->extraction               = $extraction;
        $this->keywords                 = $keywords;
        $this->country                  = $country;
        $this->targetSources            = $targetSources;
        $this->targetConfigurationNames = $targetConfigurationNames;
    }

    /**
     * Returns the percentage progress of the {@see JobOfferExtraction} run.
     * For chained checks the lower value is used for "pro-customer" approach.
     *
     * In order to understand the offers limiter case: {@see OfferExtractionLimiterService}
     *
     * @return float
     * @throws Exception
     */
    public function decide(): float
    {
        $extractionPrefix = "[Extraction: {$this->extraction->getId()}]";

        if ($this->extraction->isOffersLimitSet()) {
            if ($this->extraction->getExtractionCount() <= self::HIGHEST_OFFERS_COUNT_TO_CONSIDER_COMPARING_OTHER_EXTRACTIONS) {
                $percentageByOther = $this->decideByOtherExtractions($this->extraction->getOffersLimit());
                $this->logger->info("{$extractionPrefix} Offers limit is set, deciding by similar searches");

                if (!is_null($percentageByOther)) {
                    return $percentageByOther;
                }
            }

            return 100;
        }

        $percentage = $this->decideByHandledConfigurations();
        $this->logger->info("{$extractionPrefix} Percentage decided by amount of handled configs: {$percentage}");
        if ($percentage == 100) {
            return $percentage;
        }

        if ($percentage < self::MIN_EXTRACTION_PROGRESS_PERCENTAGE_FOR_QUANTITY_PROGRESS_CHECK) {
            $percentageByFoundAmount = $this->decideByAmountOfFoundOffers($percentage);
            $this->logger->info("{$extractionPrefix} Percentage decided by amount of found offers in current run: {$percentageByFoundAmount}");

            if ($percentageByFoundAmount < $percentage) {
                $percentage = $percentageByFoundAmount;
            }
        }

        if ($this->extraction->getExtractionCount() <= self::HIGHEST_OFFERS_COUNT_TO_CONSIDER_COMPARING_OTHER_EXTRACTIONS) {
            $percentageByOther = $this->decideByOtherExtractions();
            $this->logger->info("{$extractionPrefix} Percentage decided by count of offers in other extractions for same keyword: {$percentageByOther}");

            if (!is_null($percentageByOther) && ($percentageByOther < $percentage)) {
                $percentage = $percentageByOther;
            }
        }

        return $percentage;
    }

    /**
     * Very basic check which compares "how many configurations were expected to be handled" and
     * "how many of these configurations were successfully handled".
     *
     * Keep in mind that it doesn't care about amount of offers found etc.
     * It still can happen that search is handled successfully but returns 0 offers if for example
     * user will type incorrect keywords such as "lemon cake" etc. (as he can type in whatever he wants)
     *
     * @return float
     *
     * @throws Exception
     */
    private function decideByHandledConfigurations(): float
    {
        $expectedConfigurations = $this->targetConfigurationNames;
        if (empty($expectedConfigurations)) {
            /**
             * Keep in mind that it takes the "expected configurations" on purpose.
             * It could happen that exception gets thrown before some configurations will even be saved
             * as handled in {@see JobOfferExtraction::$configurations}, so the value assigned here will
             * always have the configurations that were supposed to be handled.
             */
            $expectedConfigurations = $this->configurationReader->getConfigurationNamesForTypes($this->country, $this->targetSources);
        }

        if (empty($expectedConfigurations)) {
            $data = json_encode([
                'targetSources' => $this->targetSources,
                'country'       => $this->country,
            ]);
            throw new LogicException("Expected configurations are empty. Data: {$data}. Extraction: {$this->extraction->getId()}");
        }

        $handledConfigurations = [];
        foreach ($this->extraction->getKeyword2Configurations() as $kw2config) {
            $handledConfigurations = array_unique([
                ...$handledConfigurations,
                ...$kw2config->getConfigurations()
            ]);
        }

        if (count($handledConfigurations) > count($expectedConfigurations)) {
            throw new LogicException("There are more handled configurations than expected! Extraction: {$this->extraction->getId()}");
        }

        $returnedPercentage = (count($handledConfigurations) / count($expectedConfigurations)) * 100;

        return $returnedPercentage;
    }

    /**
     * - {@see ExtractionProgressDeciderService::MIN_EXTRACTION_PROGRESS_PERCENTAGE_FOR_QUANTITY_PROGRESS_CHECK}
     * - {@see ExtractionProgressDeciderService::MIN_OFFERS_FOUND_FOR_QUANTITY_BASED_100_PERCENT}
     *
     * @param float $progressPercentage
     *
     * @return float
     */
    private function decideByAmountOfFoundOffers(float $progressPercentage): float
    {
        $returnedPercentage = $progressPercentage;
        if ($this->extraction->getExtractionCount() >= self::MIN_OFFERS_FOUND_FOR_QUANTITY_BASED_100_PERCENT) {
            $returnedPercentage = 100;
        }

        return $returnedPercentage;
    }

    /**
     * This decides the percentage based on other extractions made for the same keywords so far.
     * If no extractions were made for given keywords then returns null.
     *
     * @param int|null $offersLimit
     *
     * @return float|null
     */
    private function decideByOtherExtractions(?int $offersLimit = null): ?float
    {
        $higherCountFromPercentTolerance = 0;
        if ($this->extraction->getExtractionCount()) {
            $higherCountFromPercentTolerance = (int)(
                (
                      $this->extraction->getExtractionCount() * self::MAX_PERCENTAGE_DIFFERENCE_BETWEEN_OTHER_EXTRACTIONS_FOR_KW / $this->extraction->getExtractionCount()
                )   + $this->extraction->getExtractionCount()
            );
        }

        $percentageDiffPerKw        = [];
        $isAnyExtractionForKeywords = false;

        foreach ($this->keywords as $keyword) {
            $avg = $this->jobOfferExtractionRepository->getAverageOffersCountForKeyword($keyword, $offersLimit);
            if (is_null($avg)) {
                continue;
            }

            $isAnyExtractionForKeywords = true;
            if ($higherCountFromPercentTolerance >= $avg) {
                continue;
            }

            $countOfExtractedOffersForKw = $this->extraction->countOffersForKeyword($keyword);
            if (!empty($countOfExtractedOffersForKw)) {

                // this should not happen, but in case it would, skipping case where extracted count is > avg, because it's fine this way
                if ($countOfExtractedOffersForKw > $avg) {
                    continue;
                }

                $percentageDiffPerKw[] = abs((1 - $countOfExtractedOffersForKw / $avg) * 100);
            }

        }

        if (!$isAnyExtractionForKeywords) {
            return null;
        }

        if (count($percentageDiffPerKw) == 0) {
            return 0;
        }

        $mediumPercentageDiff = array_sum($percentageDiffPerKw) / count($percentageDiffPerKw);
        // should not happen but just in case
        if ($mediumPercentageDiff > 100) {
            return 100;
        }

        return $mediumPercentageDiff;
    }
}