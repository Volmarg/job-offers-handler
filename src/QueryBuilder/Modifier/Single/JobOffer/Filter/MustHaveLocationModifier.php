<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustHaveLocationModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (self::getFilter()->isMustHaveLocation()) {
            $queryBuilder->andWhere("l.id IS NOT NULL");
        }
    }
}