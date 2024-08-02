<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustExcludeCompanyName extends BaseFilterModifier implements SingleModifierInterface
{
    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (empty(self::getFilter()->getExcludedCompanyNames())) {
            return;
        }

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->notIn("LOWER(c.name)", ":companyNames")
            )->setParameter("companyNames", self::getFilter()->getExcludedCompanyNamesLowercase());
    }
}