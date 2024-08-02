<?php

namespace JobSearcher\Response\Offer;

use JobSearcher\Action\API\Offers\OffersController;
use JobSearcher\Response\BaseApiResponse;

/**
 * Related to the {@see OffersController::getSingleAnalysed()}
 */
class GetSingleOfferResponse extends BaseApiResponse
{
    /**
     * @var array $offerData
     */
    private array $offerData;

    /**
     * @return array
     */
    public function getOfferData(): array
    {
        return $this->offerData;
    }

    /**
     * @param array $offerData
     */
    public function setOfferData(array $offerData): void
    {
        $this->offerData = $offerData;
    }

}