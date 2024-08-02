<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts max pagination pages amount from delivered parameters
 */
trait MaxPaginationPagesAwareTrait
{
    use EnsureParameterKeySetTrait;

    /**
     * @param array $parameters
     *
     * @return int
     */
    public function getMaxPaginationPages(array $parameters): int
    {
        $this->ensureSet($parameters, ParametersEnum::MAX_PAGINATION_PAGES->name);
        return $parameters[ParametersEnum::MAX_PAGINATION_PAGES->name];
    }
}