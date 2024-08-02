<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use JobSearcher\Service\Location\LocationDistanceService;

/**
 * Filter offers by location distance
 */
class OfferDistanceFilter extends BaseFilter implements OfferFilterInterface
{
    public function __construct(
        private readonly LocationDistanceService $locationDistanceService
    ){}

    /**
     * {@inheritDoc}
     */
    public function filter(): array
    {
        if (!$this->getFilterDto()->isSearchByCityDistance()) {
            return $this->getOffers();
        }

        $offers                 = $this->getOffers();
        $entityIdsForExactMatch = [];
        foreach ($this->getOffers() as $offersArrayIndex => $jobOffer) {
            foreach ($jobOffer->getLocations() as $location) {
                foreach ($this->getFilterDto()->getLocationNames() as $filterLocationName) {

                    // exact same location - stays
                    if (
                            $filterLocationName === $location->getName()
                        ||  in_array($jobOffer->getId(), $entityIdsForExactMatch)
                    ) {
                        $entityIdsForExactMatch[] = $jobOffer->getId();
                        continue;
                    }

                    $distance = $this->locationDistanceService->getDistance(
                        $location->getName(),
                        $filterLocationName
                    );

                    if ($distance > $this->getFilterDto()->getMaxKmDistanceFromLocationName()) {
                        unset($offers[$offersArrayIndex]);
                    }
                }
            }
        }

        return $offers;
    }
}