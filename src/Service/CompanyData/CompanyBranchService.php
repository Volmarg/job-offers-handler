<?php

namespace JobSearcher\Service\CompanyData;

use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\Location\Location;

/**
 * Handles logic related to {@see CompanyBranch}
 */
class CompanyBranchService
{
    /**
     * Will build {@see CompanyBranch} from {@see SearchResultDto}
     *
     * @param SearchResultDto $searchResultDto
     * @param Location|null   $location
     *
     * @return CompanyBranch
     */
    public static function buildFromSearchResult(SearchResultDto $searchResultDto, ?Location $location = null): CompanyBranch
    {
        $companyBranch = new CompanyBranch();
        $companyBranch->setLocation($location);

        if( !empty($searchResultDto->getContactDetailDto()->getPhoneNumber())){
            $companyBranch->addUniquePhoneNumber($searchResultDto->getContactDetailDto()->getPhoneNumber());
        }

        return $companyBranch;
    }

}