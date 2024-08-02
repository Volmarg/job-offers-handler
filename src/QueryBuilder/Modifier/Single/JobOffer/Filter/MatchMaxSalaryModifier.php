<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MatchMaxSalaryModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (!empty(self::getFilter()->getMaxSalary())) {
            $queryBuilder
                ->andWhere("jbs.salaryMax <= :salaryMax")
                ->setParameter("salaryMax", self::getFilter()->getMaxSalary());
        }
    }
}