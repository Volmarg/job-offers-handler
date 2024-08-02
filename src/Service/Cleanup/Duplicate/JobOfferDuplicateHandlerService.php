<?php

namespace JobSearcher\Service\Cleanup\Duplicate;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\Entity\Extraction\ExtractionKeyword2Offer;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Service\Bundle\Jooblo\JoobloService;
use Psr\Log\LoggerInterface;
use TypeError;

class JobOfferDuplicateHandlerService implements DuplicateCleanupInterface
{

    /**
     * Duplicates found within the set that is going to be used for cleanup.
     * So example: {@see JobSearchResultRepository::findAllCreatedInDaysOffset()}
     * - returns offers from 24h, toward which clean is going to be handled
     * - in these 24h there might already exist duplicated offers,
     *   such offers then are stored in here
     *
     * @var JobSearchResult[] $innerDuplicates
     */
    private array $innerDuplicates = [];

    /**
     * @var JobSearchResult[] $removedDuplicates
     */
    private static array $removedDuplicates = [];

    /**
     * @var int $countOfCleared
     */
    private int $countOfCleared = 0;

    /**
     * @return array
     */
    public static function getRemovedDuplicates(): array
    {
        return self::$removedDuplicates;
    }

    /**
     * @param JobSearchResultRepository $jobSearchResultRepository
     * @param EntityManagerInterface    $entityManager
     * @param JoobloService             $joobloService
     * @param LoggerInterface           $logger
     */
    public function __construct(
        private readonly JobSearchResultRepository $jobSearchResultRepository,
        private readonly EntityManagerInterface    $entityManager,
        private readonly JoobloService             $joobloService,
        private readonly LoggerInterface           $logger
    ) {

    }

    /**
     * {@inheritDoc}
     * @throws GuzzleException
     */
    public function clean(int $maxDaysOffset, array $extractionIds = []): int
    {
        $this->entityManager->beginTransaction();
        try {
            $recentOffers = $this->jobSearchResultRepository->findAllCreatedInDaysOffset($maxDaysOffset, $extractionIds);
            $this->cleanEntities($recentOffers);
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->countOfCleared;
    }

    /**
     * @param array $entities
     *
     * @throws GuzzleException
     */
    public function cleanEntities(array $entities): void
    {
        $filteredOffers = $this->filterInnerDuplicates($entities);
        $this->mergeInnerDuplicates($filteredOffers);
    }

    /**
     * @param JobSearchResult[] $jobOffers
     *
     * @return JobSearchResult[]
     */
    private function filterInnerDuplicates(array $jobOffers): array
    {
        $filteredOffers = [];
        $duplicatedOfferIds = [];

        foreach ($jobOffers as $jobOffer) {
            if (in_array($jobOffer->getId(), $duplicatedOfferIds)) {
                $filteredOffers[] = $jobOffer;
                continue;
            }

            foreach ($jobOffers as $duplicateComparedJobOffer) {

                if (
                        $jobOffer->getId()   !== $duplicateComparedJobOffer->getId()
                    &&  $jobOffer->getAsMd5() === $duplicateComparedJobOffer->getAsMd5()
                ) {
                    $duplicatedOfferIds[]    = $duplicateComparedJobOffer->getId();
                    $this->innerDuplicates[] = $jobOffer;
                    continue 2;
                }

            }

            $filteredOffers[] = $jobOffer;
        }

        return $filteredOffers;
    }

    /**
     * @param JobSearchResult[] $jobOffers
     *
     * @throws GuzzleException
     */
    private function mergeInnerDuplicates(array $jobOffers): void
    {
        foreach ($this->innerDuplicates as $innerDuplicate) {
            foreach ($jobOffers as $jobOffer) {

                if ($innerDuplicate->getAsMd5() === $jobOffer->getAsMd5()) {

                    if ($this->joobloService->isOfferUsed($innerDuplicate)) {
                        $this->logger->warning("This offer is referenced in jooblo/voltigo so won't merge it! Id: {$innerDuplicate->getId()}");
                        continue 2;
                    }

                    $this->mergeData($jobOffer, $innerDuplicate);
                    $this->countOfCleared++;
                    continue 2;
                }

            }
        }
    }

    /**
     * Merge data from one job offer to another
     *
     * @param JobSearchResult $mergedInto
     * @param JobSearchResult $mergedFrom
     */
    private function mergeData(JobSearchResult $mergedInto, JobSearchResult $mergedFrom): void
    {
        $mergedInto->mergeKeywords($mergedFrom->getKeywords());
        $this->mergeSalaryData($mergedInto, $mergedFrom);
        $this->mergeLanguageData($mergedInto, $mergedFrom);
        $this->mergeCompanyData($mergedInto, $mergedFrom);
        $this->mergeExtractionRelations($mergedInto, $mergedFrom);

        if ($mergedInto->getLocations()->isEmpty()) {
            $mergedInto->addLocations($mergedFrom->getLocations()->getValues());
        }

        $this->entityManager->persist($mergedInto);
        $this->entityManager->flush();

        self::$removedDuplicates[] = $mergedFrom;
    }

    /**
     * @param JobSearchResult $mergedInto
     * @param JobSearchResult $mergedFrom
     */
    private function mergeSalaryData(JobSearchResult $mergedInto, JobSearchResult $mergedFrom): void
    {
        if ($mergedInto->getSalaryMin() === 0) {
            $mergedInto->setSalaryMin($mergedFrom->getSalaryMin());
        }

        if ($mergedInto->getSalaryMax() === 0) {
            $mergedInto->setSalaryMax($mergedFrom->getSalaryMax());
        }

        if ($mergedInto->getSalaryAverage() === 0) {
            $mergedInto->setSalaryAverage($mergedFrom->getSalaryAverage());
        }
    }

    /**
     * @param JobSearchResult $mergedInto
     * @param JobSearchResult $mergedFrom
     */
    private function mergeLanguageData(JobSearchResult $mergedInto, JobSearchResult $mergedFrom): void
    {
        if (!$mergedInto->getOfferLanguage()) {
            $mergedInto->setOfferLanguage($mergedFrom->getOfferLanguage());
        }

        if (!$mergedInto->getOfferLanguageIsoCodeThreeDigit()) {
            $mergedInto->setOfferLanguageIsoCodeThreeDigit($mergedFrom->getOfferLanguageIsoCodeThreeDigit());
        }

        if (!$mergedInto->getMentionedHumanLanguages()) {
            $mergedInto->setMentionedHumanLanguages($mergedFrom->getMentionedHumanLanguages());
        }
    }

    /**
     * @param JobSearchResult $mergedInto
     * @param JobSearchResult $mergedFrom
     */
    private function mergeCompanyData(JobSearchResult $mergedInto, JobSearchResult $mergedFrom): void
    {
        if (!$mergedInto->getCompanyBranch()) {
            $mergedInto->setCompanyBranch($mergedFrom->getCompanyBranch());
        }

        if (!$mergedInto->getCompany()) {
            $mergedInto->setCompany($mergedFrom->getCompany());
        }
    }

    /**
     * @param JobSearchResult $mergedInto
     * @param JobSearchResult $mergedFrom
     */
    private function mergeExtractionRelations(JobSearchResult $mergedInto, JobSearchResult $mergedFrom): void
    {
        /** @var ExtractionKeyword2Offer $extractionKeywordMergedFrom */
        foreach ($mergedFrom->getExtractionKeyword()->getValues() as $extractionKeywordMergedFrom) {
            $mergedInto->addExtractionKeyword($extractionKeywordMergedFrom);
        }

        /** @var JobOfferExtraction $offerExtraction */
        foreach ($mergedFrom->getExtractions()->getValues() as $offerExtraction) {
            $mergedInto->addExtraction($offerExtraction);
            $offerExtraction->addJobSearchResult($mergedInto);
            $this->entityManager->persist($offerExtraction);
        }
    }

}