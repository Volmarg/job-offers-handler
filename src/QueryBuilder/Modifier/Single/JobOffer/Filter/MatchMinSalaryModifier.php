<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MatchMinSalaryModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (!empty(self::getFilter()->getMinSalary())) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        "jbs.salaryMin >= :salaryMin",
                        "jbs.salaryAverage >= :salaryMin"
                    )
                )
                ->setParameter("salaryMin", self::getFilter()->getMinSalary());
        }

    }
}