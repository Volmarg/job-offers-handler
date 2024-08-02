<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use JobSearcher\Service\Normalizer\Location\CityNameNormalizer;

/**
 * Filter out offers not matching the selected location names
 */
class LocationNameFilter extends BaseFilter implements OfferFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function filter(): array
    {
        if (empty($this->getFilterDto()->getLocationNames())) {
            return $this->getOffers();
        }

        $filteredOffers = [];
        foreach ($this->getOffers() as $jobOffer) {

            if ($jobOffer->getLocations()->isEmpty()) {
                continue;
            }

            foreach ($jobOffer->getLocations() as $location) {
                if (empty($location->getName())) {
                    continue;
                }

                $normalizedLocationName = CityNameNormalizer::normalize($location->getName());
                if (in_array($normalizedLocationName, $this->getFilterDto()->getLocationNames())) {
                    $filteredOffers[] = $jobOffer;
                }

            }

        }

        return $filteredOffers;
    }
}