<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

/**
 * Will decide if job offer is suitable in terms of human languages
 */
class HumanLanguageFilter extends BaseFilter implements OfferFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function filter(): array
    {
        $filteredOffers = [];
        foreach ($this->getOffers() as $offer) {

            $hasHumanLanguagesMentioned = !empty($offer->getMentionedHumanLanguages());
            if (
                    $this->getFilterDto()->isTreatOfferDescriptionLanguageAsHumanLanguage()
                &&  in_array($offer->getOfferLanguage(), $this->getFilterDto()->getMandatoryHumanLanguages())
            ) {
                $filteredOffers[] = $offer;
                continue;
            }

            if(
                (
                        !$hasHumanLanguagesMentioned
                    &&  !empty($this->getFilterDto()->getMandatoryHumanLanguages())
                )
                ||
                (
                        $hasHumanLanguagesMentioned
                    &&  !empty(array_diff($this->getFilterDto()->getMandatoryHumanLanguages(), $offer->getMentionedHumanLanguages()))
                )
                ||
                (
                        !$this->getFilterDto()->isIncludeJobOffersWithoutHumanLanguagesMentioned()
                    &&  !$hasHumanLanguagesMentioned
                )
            ){
                continue;
            }

            $filteredOffers[] = $offer;
        }

        return $filteredOffers;
    }
}