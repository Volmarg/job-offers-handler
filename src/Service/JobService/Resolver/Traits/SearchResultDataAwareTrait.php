<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts search result data from delivered parameters
 */
trait SearchResultDataAwareTrait
{
    use EnsureParameterKeySetTrait;

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function getSearchResultData(array $parameters): array
    {
        $this->ensureSet($parameters, ParametersEnum::SEARCH_RESULT_DATA->name);
        return $parameters[ParametersEnum::SEARCH_RESULT_DATA->name];
    }
}