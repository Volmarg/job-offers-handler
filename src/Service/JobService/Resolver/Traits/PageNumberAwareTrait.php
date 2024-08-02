<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts page number from delivered parameters
 */
trait PageNumberAwareTrait
{
    /**
     * @param array $parameters
     *
     * @return null|string
     */
    public function getPageNumber(array $parameters): ?string
    {
        return $parameters[ParametersEnum::PAGE_NUMBER->name];
    }
}