<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts keywords from delivered parameters
 */
trait LocationDistanceAwareTrait
{
    /**
     * @param array $parameters
     *
     * @return int|null
     */
    public function getLocationDistance(array $parameters): ?int
    {
        return $parameters[ParametersEnum::LOCATION_DISTANCE->name];
    }
}