<?php

namespace JobSearcher\Service\Extraction\Offer;

use DateTime;
use JobSearcher\Command\Cleanup\OffersCleanupCommand;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Provides logic for {@see OffersCleanupCommand}
 */
class OfferExtractionCleanupService
{
    private readonly int $maxDaysExtractionWithOffersLifetime;

    public function __construct(
        readonly ParameterBagInterface $parameterBag
    ){
        $this->maxDaysExtractionWithOffersLifetime = $parameterBag->get("max_days_extraction_with_offers_lifetime");
    }

    /**
     * Check if given extraction itself can be removed
     *
     * @param JobOfferExtraction $extraction
     *
     * @return bool
     */
    public function canExtractionBeRemoved(JobOfferExtraction $extraction): bool
    {
        $maxLifeDate = (clone $extraction->getCreated())->modify("+{$this->maxDaysExtractionWithOffersLifetime} DAYS");

        return ((new DateTime())->getTimestamp() > $maxLifeDate->getTimestamp());
    }
}