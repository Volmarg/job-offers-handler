<?php

namespace JobSearcher\Service\JobSearch\Result;

use JobSearcher\Command\Cleanup\OffersCleanupCommand;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Service\Extraction\Offer\OfferExtractionCleanupService;

/**
 * Provides logic related to {@see OffersCleanupCommand}
 */
class SearchResultCleanupService
{
    public function __construct(
        private readonly OfferExtractionCleanupService $offerExtractionCleanupService
    ){}

    /**
     * Will check if given offer can be removed or not
     *
     * @param JobSearchResult $jobSearchResult
     *
     * @return bool
     */
    public function canOfferBeRemoved(JobSearchResult $jobSearchResult): bool
    {
        if ($jobSearchResult->getExtractionsCount() > 1) {
            foreach ($jobSearchResult->getExtractions() as $extraction) {

                if (!$this->offerExtractionCleanupService->canExtractionBeRemoved($extraction)) {
                    return false;
                }

            }
        }

        return true;
    }
}