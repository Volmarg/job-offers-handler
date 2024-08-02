<?php

namespace JobSearcher\Service\Filters;

use JobSearcher\DTO\Api\Transport\Filter\FilterValuesDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\Filters\Provider\JobOffers\OffersRelatedProviderInterface;

/**
 * Handles providing filter values
 */
class FilterValuesService
{
    /**
     * @var JobOfferExtraction $extraction
     */
    private JobOfferExtraction $extraction;

    /**
     * @var array $offerIds
     */
    private array $offerIds = [];

    /**
     * @return array
     */
    public function getOfferIds(): array
    {
        return $this->offerIds;
    }

    /**
     * @param array $offerIds
     */
    public function setOfferIds(array $offerIds): void
    {
        $this->offerIds = $offerIds;
    }

    /**
     * @return JobOfferExtraction
     */
    public function getExtraction(): JobOfferExtraction
    {
        return $this->extraction;
    }

    /**
     * @param JobOfferExtraction $extraction
     */
    public function setExtraction(JobOfferExtraction $extraction): void
    {
        $this->extraction = $extraction;
    }

    /**
     * @param OffersRelatedProviderInterface[] $offersProviders
     */
    public function __construct(
        private readonly array $offersProviders
    ){}

    /**
     * Generic function that collects all the values and returns them as {@see FilterValuesService}
     *
     * The {@see FilterValuesDto} provided to each providing set gets modified in the process of providing,
     * basically the filer props are being set / changed
     *
     * @return FilterValuesDto
     */
    public function provide(): FilterValuesDto
    {
        $filterValuesDto =  new FilterValuesDto();
        $filterValuesDto = $this->provideForOffers($filterValuesDto);

        return $filterValuesDto;
    }

    /**
     * {@see FilterValuesService::provide} but it provides the values related to the offers
     *
     * @param FilterValuesDto $filterValuesDto
     *
     * @return FilterValuesDto
     */
    public function provideForOffers(FilterValuesDto $filterValuesDto): FilterValuesDto
    {
        foreach ($this->offersProviders as $offersProvider) {
            $offersProvider->setExtraction($this->getExtraction());
            $offersProvider->setOfferIds($this->getOfferIds());
            $filterValuesDto = $offersProvider->provide($filterValuesDto);
        }

        return $filterValuesDto;
    }
}
