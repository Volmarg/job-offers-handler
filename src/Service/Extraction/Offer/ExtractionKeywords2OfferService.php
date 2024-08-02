<?php

namespace JobSearcher\Service\Extraction\Offer;

use JobSearcher\Entity\Extraction\ExtractionKeyword2Offer;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;

/**
 * Provides the logic for {@see ExtractionKeyword2Offer}
 */
class ExtractionKeywords2OfferService
{
    /**
     * Creates {@see 2ExtractionKeyword2Offer} for provided keyword and {@see JobSearchResult}
     *
     * @param string             $keyword
     * @param JobSearchResult    $jobOffer
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @return ExtractionKeyword2Offer
     */
    public function createEntity(string $keyword, JobSearchResult $jobOffer, JobOfferExtraction $jobOfferExtraction): ExtractionKeyword2Offer
    {
        $entity = new ExtractionKeyword2Offer();
        $entity->setKeyword($keyword);
        $entity->setJobOffer($jobOffer);

        $jobOfferExtraction->addKeywords2Offer($entity);

        return $entity;
    }
}