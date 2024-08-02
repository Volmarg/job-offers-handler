<?php

namespace JobSearcher\Service\JobSearch\Result\Filter;

use JobSearcher\Entity\JobSearchResult\JobSearchResult;

/**
 * Describes the offer filter interface
 */
interface OfferFilterInterface
{
    /**
     * Will filter out the offers
     *
     * @return JobSearchResult[]
     */
    public function filter(): array;
}