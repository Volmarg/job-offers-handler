<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

/**
 * Filter out duplicated offers
 *
 * 1. There are cases where the same offers are posted multiple times by the same company, but the company is using
 *    different name such as <Company A>, <Company A Gmbh>, <Company A Public>,
 */
class SameOffersFilter extends BaseFilter implements OfferFilterInterface
{
    private array $addedDuplicateHashes = [];

    private const COMPANY_NAME_MIN_SIMILARITY = 80;

    /**
     * {@inheritDoc}
     */
    public function filter(): array
    {
        $filteredOffers = [];
        foreach ($this->getOffers() as $jobOffer) {

            foreach ($this->getOffers() as $jobOfferToCheck) {
                if ($jobOffer->getId() === $jobOfferToCheck->getId()) {
                    continue;
                }

                // similar_text comparison is heavy so if it was already marked as "added duplicate" then let's just do hash check
                $offerHash = md5($jobOffer->getCompany()->getName() . $jobOffer->getJobTitle());
                if (in_array($offerHash, $this->addedDuplicateHashes)) {
                    continue 2;
                }

                // same company (under bit other name) and same offer title
                similar_text($jobOffer->getCompany()->getName(), $jobOfferToCheck->getCompany()->getName(), $companySimilarityPercent);
                if(
                    $jobOffer->getJobTitle() === $jobOfferToCheck->getJobTitle()
                    &&  $companySimilarityPercent >= self::COMPANY_NAME_MIN_SIMILARITY
                ) {
                    if (!in_array($offerHash, $this->addedDuplicateHashes)) {
                        $this->addedDuplicateHashes[] = $offerHash;
                        $filteredOffers[]             = $jobOffer;
                    }

                    continue 2;
                }

            }
            $filteredOffers[] = $jobOffer;
        }

        return $filteredOffers;
    }
}