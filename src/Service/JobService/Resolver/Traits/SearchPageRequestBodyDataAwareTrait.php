<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts detail page request body data from delivered parameters
 */
trait SearchPageRequestBodyDataAwareTrait
{
    use EnsureParameterKeySetTrait;

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function getSearchPageRequestBodyData(array $parameters): array
    {
        $this->ensureSet($parameters, ParametersEnum::SEARCH_PAGE_REQUEST_BODY_DATA->name);
        return $parameters[ParametersEnum::SEARCH_PAGE_REQUEST_BODY_DATA->name];
    }
}