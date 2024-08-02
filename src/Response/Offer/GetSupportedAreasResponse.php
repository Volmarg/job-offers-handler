<?php

namespace JobSearcher\Response\Offer;

use JobSearcher\Action\API\JobServices\CountryAreaController;
use JobSearcher\Response\BaseApiResponse;

/**
 * Related to the {@see CountryAreaController::getSupportedAreas()}
 */
class GetSupportedAreasResponse extends BaseApiResponse
{
    /**
     * @var array $areaNames
     */
    private array $areaNames = [];

    /**
     * @return array
     */
    public function getAreaNames(): array
    {
        return $this->areaNames;
    }

    /**
     * @param array $areaNames
     */
    public function setAreaNames(array $areaNames): void
    {
        $this->areaNames = $areaNames;
    }

}