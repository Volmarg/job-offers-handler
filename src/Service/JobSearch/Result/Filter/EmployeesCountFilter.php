<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use JobSearcher\Service\TypeProcessor\RangeTypeProcessor;

/**
 * Decide if company min employees count is matching
 * This cannot be done on DB side because the employees count is often saved as `ranges`
 */
class EmployeesCountFilter extends BaseFilter implements OfferFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function filter(): array
    {
        $filteredOffers = [];
        foreach($this->getOffers() as $offer){
            $employeeCountHigh = RangeTypeProcessor::extractHighestValue($offer->getCompany()->getEmployeesRange());

            if (
                    $employeeCountHigh
                &&  $this->getFilterDto()->getCompanyEmployeesMinCount()
                &&  $employeeCountHigh < $this->getFilterDto()->getCompanyEmployeesMinCount()
            ) {
                continue;
            }

            if(
                    empty($employeeCountHigh)
                &&  !$this->getFilterDto()->isIncludeOffersWithoutEmployeesCount()
            ){
                continue;
            }

            $filteredOffers[] = $offer;
        }

        return $filteredOffers;
    }
}