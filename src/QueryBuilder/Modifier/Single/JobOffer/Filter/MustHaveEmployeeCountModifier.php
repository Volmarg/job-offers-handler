<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustHaveEmployeeCountModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (self::getFilter()->isEmployeeCountRequired()) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->isNotNull("c.employeesRange")
                )->andWhere(
                    $queryBuilder->expr()->neq("c.employeesRange", "''")
                );
        }
    }
}