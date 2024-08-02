<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use JobSearcher\DTO\Api\Transport\Offer\ExcludedOfferData;

/**
 * Will return only the offers that ar not matching the provided offer ids
 */
class ExcludedOffersFilter extends BaseFilter implements OfferFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function filter(): array
    {
        if (empty($this->getExcludedOffersDtos())) {
            return $this->getOffers();
        }

        $filteredOffers   = [];
        $addedOffersIds   = [];
        $excludedOfferIds = array_map(
            fn(ExcludedOfferData $excludedOfferData) => $excludedOfferData->getId(),
            $this->getExcludedOffersDtos(),
        );

        foreach ($this->getOffers() as $jobOffer) {
            if(
                    !in_array($jobOffer->getId(), $excludedOfferIds)
                &&  !in_array($jobOffer->getId(), $addedOffersIds)
            ){
                $filteredOffers[] = $jobOffer;
                $addedOffersIds[] = $jobOffer->getId();
                continue;
            }

            foreach ($this->getExcludedOffersDtos() as $excludedOfferData) {
                if ($jobOffer->getId() !== $excludedOfferData->getId()) {
                    continue;
                }

                if (
                    !in_array($jobOffer->getId(), $addedOffersIds)
                    &&  $jobOffer->getJobTitle()            !== $excludedOfferData->getTitle()
                    &&  $jobOffer->getCompany()?->getName() !== $excludedOfferData->getCompanyName()
                ) {
                    $filteredOffers[] = $jobOffer;
                    $addedOffersIds[] = $jobOffer->getId();
                }
            }
        }

        return $filteredOffers;
    }
}