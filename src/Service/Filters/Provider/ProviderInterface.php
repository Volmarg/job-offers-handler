<?php

namespace JobSearcher\Service\Filters\Provider;

use JobSearcher\DTO\Api\Transport\Filter\FilterValuesDto;

/**
 * Describes generic logic for filters data providers
 */
interface ProviderInterface
{
    /**
     * Provides any kind of necessary data for the filters functionality
     *
     * @param mixed $filterValues
     *
     * @return FilterValuesDto
     */
    public function provide(FilterValuesDto $filterValues): FilterValuesDto;
}