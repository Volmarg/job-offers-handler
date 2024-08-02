<?php

namespace JobSearcher\Service\Filters\Provider\JobOffers;

use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\Filters\Provider\ProviderInterface;

/**
 * {@see ProviderInterface} but in this case describes what provider should have in order to provide
 * the data for offers related filters
 */
interface OffersRelatedProviderInterface extends ProviderInterface
{
    /**
     * @return JobOfferExtraction
     */
    public function getExtraction(): JobOfferExtraction;

    /**
     * @param JobOfferExtraction $extraction
     */
    public function setExtraction(JobOfferExtraction $extraction): void;

    /**
     * @return array
     */
    public function getOfferIds(): array;

    /**
     * @param array $offerIds
     */
    public function setOfferIds(array $offerIds): void;
}