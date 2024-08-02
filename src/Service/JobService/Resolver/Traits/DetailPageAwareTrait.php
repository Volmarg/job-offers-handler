<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts detail page from delivered parameters
 */
trait DetailPageAwareTrait
{
    /**
     * @param array $parameters
     *
     * @return null|string
     */
    public function getDetailPageUrl(array $parameters): ?string
    {
        return $parameters[ParametersEnum::DETAIL_PAGE_URL->name];
    }
}