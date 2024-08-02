<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts search uri from delivered parameters
 */
trait SearchUriAwareTrait
{
    /**
     * @param array $parameters
     *
     * @return null|string
     */
    public function getSearchUri(array $parameters): ?string
    {
        return $parameters[ParametersEnum::SEARCH_URI->name];
    }
}