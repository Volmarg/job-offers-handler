<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustIncludeCompanyName extends BaseFilterModifier implements SingleModifierInterface
{
    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (empty(self::getFilter()->getIncludedCompanyNames())) {
            return;
        }

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->in("LOWER(c.name)", ":companyNames")
            )->setParameter("companyNames", self::getFilter()->getIncludedCompanyNamesLowercase());
    }
}