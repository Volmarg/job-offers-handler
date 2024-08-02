<?php

namespace JobSearcher\Response\Offer;

use JobSearcher\Action\API\Offers\OffersController;
use JobSearcher\Response\BaseApiResponse;

/**
 * {@see OffersController::getOfferFullDescription()}
 */
class GetFullDescriptionResponse extends BaseApiResponse
{

    /**
     * @var string $description
     */
    private string $description;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

}