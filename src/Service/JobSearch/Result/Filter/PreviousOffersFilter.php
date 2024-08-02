<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;

/**
 * If offers bound to given extraction wer already found once
 * then these will be excluded from returned set of offers:
 * - only newly found offers will get returned
 *
 * This is must because search "A" will create offers,
 * then search "B" with same criteria might find the same offers,
 * so the offers found in "A" are bound to "B" as well,
 *
 * But the new offers not found earlier are only the ones that are found to the "B"
 */
class PreviousOffersFilter extends BaseFilter implements OfferFilterInterface
{
    /**
     * @param JobSearchResultRepository $jobSearchResultRepository
     */
    public function __construct(
        private readonly JobSearchResultRepository $jobSearchResultRepository
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function filter(): array
    {
        if (
                !$this->getFilterDto()->isOnlyNewOffers()
            &&  !empty($this->getCurrentlyHandledExtraction())
        ) {
            return $this->getOffers();
        }

        $filteredOffers = [];
        foreach ($this->getOffers() as $jobOffer) {
            if (!$this->isBoundToAnyEarlierExtraction($jobOffer, $this->getUserExtractionIds())) {
                $filteredOffers[] = $jobOffer;
            }
        }

        return $filteredOffers;
    }

    /**
     * Check if given offer is bound to more than just one extraction
     *
     * @param JobSearchResult $jobOffer
     * @param int[] $userExtractionIds
     *
     * @return bool
     */
    private function isBoundToAnyEarlierExtraction(JobSearchResult $jobOffer, array $userExtractionIds): bool
    {
        return $this->jobSearchResultRepository->isOfferBoundToEarlierExtractions(
            $this->getCurrentlyHandledExtraction(),
            $jobOffer,
            $userExtractionIds
        );
    }

}