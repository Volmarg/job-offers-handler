<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Modifier\Single\BaseSingleModifier;

/**
 * Provides base logic for the use cases of {@see JobOfferFilterDto}
 */
class BaseFilterModifier extends BaseSingleModifier
{
    private const FILTER = "filter";

    /**
     * Will return the filter dto
     *
     * @return JobOfferFilterDto
     * @throws DataNotFoundException
     */
    public static function getFilter(): JobOfferFilterDto
    {
        return self::getDataForKey(self::FILTER);
    }

    /**
     * Will set the filter dto as usable data
     *
     * @param JobOfferFilterDto $jobOfferFilterDto
     */
    public static function setFilterDto(JobOfferFilterDto $jobOfferFilterDto): void
    {
        self::addDataKey(self::FILTER, $jobOfferFilterDto);
    }
}