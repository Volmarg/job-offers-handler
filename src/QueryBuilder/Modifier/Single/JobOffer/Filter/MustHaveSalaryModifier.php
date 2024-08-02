<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustHaveSalaryModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (self::getFilter()->isMustHaveSalary()) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        "jbs.salaryMin != 0",
                        "jbs.salaryAverage != 0"
                    )
                );
        }
    }
}