<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts keywords from delivered parameters
 */
trait LocationNameAwareTrait
{
    /**
     * @param array $parameters
     *
     * @return null|string
     */
    public function getLocationName(array $parameters): ?string
    {
        return $parameters[ParametersEnum::LOCATION_NAME->name];
    }
}